<?php

final class SassLinter extends ConfigPathLinter {

    public function getDefaultBinary() {
        $config = $this->getEngine()->getConfigurationManager();
        return $config->getConfigFromAnySource('bin.sass', 'scss-lint');
    }

    public function getMandatoryFlags() {
        return array(
            '--require',
            'scss_lint_reporter_checkstyle',
            '-f',
            'Checkstyle',
        );
    }

    public function getInstallInstructions() {
        return pht('run sudo gem install scss-lint scss_lint_reporter_checkstyle');
    }

    public function getLinterName() {
        return 'sass';
    }

    public function getLinterConfigurationName() {
        return 'sass';
    }

    public function shouldExpectCommandErrors() {
        return true;
    }

    protected function parseLinterOutput($path, $err, $stdout, $stderr) {
        if (!$stdout) {
            throw new ArcanistUsageException('The linter produced no output. '
                .'This might be a bug, so I\'m showing you the stderr below:'
                ."\n".$stderr
                ."\nRemember: this linter requires both `scss-lint` and "
                .'`scss_lint_reporter_checkstyle` to run.');
        }

        $paths = array($path);
        $parser = new CheckstyleParser();
        $parser->setName('Sass', 'SASS');
        return $parser->parseContent($stdout, $paths);
    }
}
