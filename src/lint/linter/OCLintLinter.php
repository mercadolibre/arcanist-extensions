<?php

final class OCLintLinter extends ArcanistSingleRunLinter {

    private $oclintBin = 'oclint';

    private $xcprettyBin = 'xcpretty';

    private $workspace;

    private $scheme;

    private $configuration;

    private $sdk;

    public function getLinterConfigurationOptions() {
        $options = parent::getLinterConfigurationOptions();

        $options['workspace'] = array(
            'type' => 'string',
            'help' => 'The .xcworkspace to build on',
        );

        $options['scheme'] = array(
            'type' => 'string',
            'help' => 'The workspace scheme to build',
        );

        $options['configuration'] = array(
            'type' => 'string',
            'help' => 'Target build configuration',
        );

        $options['sdk'] = array(
            'type' => 'string',
            'help' => 'SDK to build against to',
        );

        $options['oclint'] = array(
            'type' => 'optional string',
            'help' => 'Path to oclint-json-compilation-database',
        );

        $options['xcpretty'] = array(
            'type' => 'optional string',
            'help' => 'Path to xcpretty executable',
        );

        return $options;
    }

    public function setLinterConfigurationValue($key, $value) {
        switch ($key) {
        case 'scheme':
            $this->scheme = $value;
            return;
        case 'workspace':
            $this->workspace = $value;
            return;
        case 'sdk':
            $this->sdk = $value;
            return;
        case 'configuration':
            $this->configuration = $value;
            return;
        case 'oclint':
            $this->oclintBin = $value;
            return;
        case 'xcpretty':
            $this->xcprettyBin = $value;
            return;
        }
        return parent::setLinterConfigurationValue($key, $value);
    }

    protected function getDefaultBinary() {

        list($err, $stdout) = exec_manual('which %s', $this->oclintBin);
        if ($err) {
            throw new ArcanistUsageException("can't find oclint");
        }

        return trim($stdout);
    }

    private function getXCPrettyPath() {

        list($err, $stdout) = exec_manual('which %s', $this->xcprettyBin);
        if ($err) {
            throw new ArcanistUsageException("can't find xcpretty");
        }

        return trim($stdout);
    }

    public function getLinterName() {
        return 'oclint';
    }

    public function getLinterConfigurationName() {
        return 'oclint';
    }

    protected function getPathsArgumentForLinter($paths) {
        $quoted_paths = array();
        foreach ($paths as $path) {
            $quoted_paths[] = escapeshellarg(addcslashes($path, ' '));
        }

        return implode(' ', $quoted_paths);
    }

    protected function getMandatoryFlags() {
        $working_copy = $this->getEngine()->getWorkingCopy();
        $root = $working_copy->getProjectRoot();

        return array(
            '-p "'.$root.'"',
        );
    }

    protected function getDefaultFlags() {
        $config = $this->getEngine()->getConfigurationManager();
        return $config->getConfigFromAnySource('lint.oclint.options', array());
    }

    protected function prepareToLintPaths(array $paths) {

        $result = exec_manual('xcodebuild '
            .'-workspace %s '
            .'-scheme %s '
            .'-configuration %s '
            .'-sdk %s '
            .'-dry-run '
            .'clean build '
            .'| %s '
            .'--report json-compilation-database '
            .'--output compile_commands.json ',
            $this->workspace,
            $this->scheme,
            $this->configuration,
            $this->sdk,
            $this->getXCPrettyPath());

        if ($result[0]) {
            throw new Exception('failed executing xcodebuild'
                .PHP_EOL.$result[2]);
        }
    }

    protected function parseLinterOutput($paths, $err, $stdout, $stderr) {
        $error_regexp = '/(?<file>[^:]+):(?P<line>\d+):(?P<col>\d+):'
            .' (?<name>.*) (?<priority>P[0-9]) (?<desc>.*)/is';
        $messages = array();

        if ($stdout === '') {
            return $messages;
        }

        foreach (explode("\n", $stdout) as $line) {
            $matches = array();
            if ($c = preg_match($error_regexp, $line, $matches)) {
                $name = $matches['name'];
                $complete_code = 'OCLINT.'.strtoupper(str_replace(' ', '_', $name));

                // Trim to 32 just in case, conduit goes boom otherwise
                $code = substr($complete_code, 0, 32);

                $message = new ArcanistLintMessage();
                $message->setPath($matches['file']);
                $message->setLine(intval($matches['line']));
                $message->setChar(intval($matches['col']));
                $message->setCode($code);
                $message->setName($name);
                $message->setDescription($matches['desc']);

                $message->setSeverity(ArcanistLintSeverity::SEVERITY_WARNING);


                $messages[] = $message;
            }
        }
        return $messages;
    }

}
