<?php

final class ShellcheckLinter extends ArcanistSingleRunLinter {

    public function getDefaultBinary() {
        $config = $this->getEngine()->getConfigurationManager();
        return $config->getConfigFromAnySource('bin.shellcheck', 'shellcheck');
    }

    public function getMandatoryFlags() {
        return array(
            '--format',
            'checkstyle',
        );
    }

    public function getDefaultFlags() {
        return array();
    }

    public function getInstallInstructions() {
        return 'See: https://github.com/koalaman/shellcheck';
    }

    public function getLinterName() {
        return 'shellcheck';
    }

    public function getLinterConfigurationName() {
        return 'shellcheck';
    }

    public function shouldExpectCommandErrors() {
        return true;
    }

    protected function getPathsArgumentForLinter($paths) {
        return implode(' ', $paths);
    }

    protected function parseLinterOutput($paths, $err, $stdout, $stderr) {

        if (!$stdout) {
            throw new ArcanistUsageException('The linter failed to produce '
                .'a meaningful response. Paste the following in your bug '
                ."report, please.\n"
                .$stderr);
        }

        $parser = new CheckstyleParser();
        $parser->setName('Shellcheck', 'SC');
        return $parser->parseContent($stdout, $paths);
    }

}
