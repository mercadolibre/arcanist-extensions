<?php

final class TSLintLinter extends ConfigPathLinter {

    public function getDefaultBinary() {
        $config = $this->getEngine()->getConfigurationManager();
        return $config->getConfigFromAnySource('bin.tslint', 'tslint');
    }

    public function getMandatoryFlags() {
        return array(
            '--format',
            'checkstyle',
        );
    }

    public function getInstallInstructions() {
        return 'Install via npm (npm install -g tslint). ';
    }

    public function getLinterName() {
        return 'tslint';
    }

    public function getLinterConfigurationName() {
        return 'tslint';
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
        $parser->setName('tslint', 'tslint');
        return $parser->parseContent($stdout, $paths);
    }
}
