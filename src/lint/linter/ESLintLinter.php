<?php

final class ESLintLinter extends ConfigPathLinter {

    public function getDefaultBinary() {
        $config = $this->getEngine()->getConfigurationManager();
        return $config->getConfigFromAnySource('bin.eslint', 'eslint');
    }

    public function getMandatoryFlags() {
        return array(
            '--format',
            'checkstyle',
        );
    }

    public function getInstallInstructions() {
        return 'Install eslint via npm (npm install -g eslint). '
            .'Much obliged.';
    }

    public function getLinterName() {
        return 'eslint';
    }

    public function getLinterConfigurationName() {
        return 'eslint';
    }

    public function shouldExpectCommandErrors() {
        return true;
    }

    protected function parseLinterOutput($path, $err, $stdout, $stderr) {
        if (!$stdout) {
            throw new ArcanistUsageException('The linter failed to produce '
                .'a meaningful response. Paste the following in your bug '
                ."report, please.\n"
                .$stderr);
        }

        // arcanist lints on a file by file basis.
        $paths = array($path);
        $parser = new CheckstyleParser();
        $parser->setName('ESLint', 'ESLINT');
        return $parser->parseContent($stdout, $paths);
    }
}
