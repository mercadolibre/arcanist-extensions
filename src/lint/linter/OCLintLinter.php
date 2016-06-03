<?php

final class OCLintLinter extends ArcanistSingleRunLinter {

    public function getInfoDescription() {
        return 'This uses xcodebuild, xcpretty and oclint.'.PHP_EOL
            .'It assumes you use cocoapods for project organization. You can configure the following keys:'.PHP_EOL
            .XcodebuildConfiguration::getOptionsDescription();
    }

    protected function getDefaultBinary() {
        $config = $this->getEngine()->getConfigurationManager();
        $oclint_bin = $config->getConfigFromAnySource('bin.oclint', 'oclint');

        list($err, $stdout) = exec_manual('which %s', $oclint_bin);
        if ($err) {
            throw new ArcanistUsageException("can't find oclint");
        }

        return trim($stdout);
    }

    private function getXCPrettyPath() {
        $config = $this->getEngine()->getConfigurationManager();
        $xcpretty_bin = $config->getConfigFromAnySource('bin.xcpretty', 'xcpretty');

        list($err, $stdout) = exec_manual('which %s', $xcpretty_bin);
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
        $configuration_manager = $this->getEngine()->getConfigurationManager();
        $configuration = new XcodebuildConfiguration($configuration_manager);

        $build_flags = array('-dry-run', 'clean', 'build');
        $result = exec_manual($configuration->buildCommand($build_flags)
            .'| %s '
            .'--report json-compilation-database '
            .'--output compile_commands.json ',
            $this->getXCPrettyPath());

        if ($result[0]) {
            throw new Exception('failed executing xcodebuild'
                .PHP_EOL.$result[2]);
        }
    }

    protected function parseLinterOutput($paths, $err, $stdout, $stderr) {
        $error_regexp = '/(?<file>[^:]+):(?P<line>\d+):(?P<col>\d+):'
            .' (?<name>.*) \[(?<category>[^|]+)\|(?<priority>P[0-9])\] (?<desc>.*)/is';
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
                $message->setLine((int)$matches['line']);
                $message->setChar((int)$matches['col']);
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
