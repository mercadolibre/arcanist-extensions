<?php

final class ArcanistOCLintLinter extends ArcanistLinter {

    private $_xctoolPath = '/opt/xctool/xctool.sh';

    private $_oclintBin = 'oclint';

    public function getLinterConfigurationOptions() {
        $options = parent::getLinterConfigurationOptions();

        $options['xctool'] = array(
            'type' => 'optional string',
            'help' => 'Path to xctool.sh'
        );

        $options['oclint'] = array(
            'type' => 'optional string',
            'help' => 'Path to oclint-json-compilation-database'
        );

        return $options;
    }

    public function setLinterConfigurationValue($key, $value) {
        switch($key) {
        case 'xctool':
            $this->_xctoolPath = $value;
            return;
        case 'oclint':
            $this->_oclintBin = $value;
            return;
        }
        return parent::setLinterConfigurationValue($key, $value);
    }

    private function getDefaultBinary() {

        list($err, $stdout) = exec_manual('which %s', $this->_oclintBin);
        if ($err) {
            throw new ArcanistUsageException("can't find oclint");
        }

        return trim($stdout);
    }

    private function getXCToolPath() {

        list($err, $stdout) = exec_manual('which %s', $this->_xctoolPath);
        if ($err) {
            throw new ArcanistUsageException("can't find xctool");
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
            '-p "' . $root . '"'
        );
    }

    protected function getDefaultFlags() {
        $config = $this->getEngine()->getConfigurationManager();
        return $config->getConfigFromAnySource('lint.oclint.options', array());
    }

    final public function lintPath($path) {
    }

    final public function willLintPaths(array $paths) {

        $working_copy = $this->getEngine()->getWorkingCopy();
        $root = $working_copy->getProjectRoot();
        chdir($root);

        $result = exec_manual('%s -reporter'
            . ' json-compilation-database:compile_commands.json clean build',
            $this->getXCToolPath());
        if ($result[0]) {
            throw new Exception('failed executing xctool'
                . PHP_EOL . $result[2]);
        }

        $result = exec_manual($this->buildCommand($paths));
        $messages = $this->parseLinterOutput($paths,
            $result[0], $result[1], $result[2]);

        foreach ($messages as $message) {
            $this->addLintMessage($message);
        }
    }

    final protected function buildCommand($paths) {
        $binary = $this->getDefaultBinary();
        $args = implode(' ', $this->getMandatoryFlags());
        $args = $args . implode(' ', $this->getDefaultFlags());
        $paths = $this->getPathsArgumentForLinter($paths);
        return "$binary $args $paths";
    }

    final public function didRunLinters() {
        foreach ($this->paths as $path) {
            $this->willLintPath($path);
        }
    }

    protected function parseLinterOutput($paths, $err, $stdout, $stderr) {
        $errorRegex = '/(?<file>[^:]+):(?P<line>\d+):(?P<col>\d+):'
            . ' (?<error>.*)/is';
        $messages = array();

        if ($stdout === '') {
            return $messages;
        }

        foreach (explode("\n", $stdout) as $line) {
            $matches = array();
            if ($c = preg_match($errorRegex, $line, $matches)) {

                $message = new ArcanistLintMessage();
                $message->setPath($matches['file']);
                $message->setLine($matches['line']);
                $message->setChar($matches['col']);
                $message->setCode($matches['error']);

                $message->setSeverity(ArcanistLintSeverity::SEVERITY_WARNING);

                $message->setName('OCLINT.' . $matches['error']);

                $messages[] = $message;
            }
        }
        return $messages;
    }

}
