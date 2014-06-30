
<?php

final class ArcanistOCLintLinter extends ArcanistLinter {

    public function getLinterName() {
        return 'oclint';
    }

    public function getLinterConfigurationName() {
        return 'oclint';
    }

    protected function getOCLintOpts() {
        $config = $this->getEngine()->getConfigurationManager();
        $opts = $config->getConfigFromAnySource('lint.oclint.options', array());

        array_push($opts, '-report-type', 'text');

        return $opts;
    }

    protected function getCompilerOpts() {
        $config = $this->getEngine()->getConfigurationManager();
        $compilerOpts = $config->getConfigFromAnySource('lint.oclint.options', array());

        array_push($compilerOpts, '-x', 'objective-c', '-c');

        return $compilerOpts;
    }

    private function parseOCLintOutput($output) {
        $errorRegex = "/(?<file>(\/(\w|-|\.)+)+):(?P<line>\d+):(?P<col>\d+): (?P<severity>\w+): (?<error>.*)/is";
        $messages = array();
        foreach ($output as $line) {
            $matches = array();
            if ($c = preg_match($errorRegex, $line, $matches)) {
                $message = new ArcanistLintMessage();
                $message->setPath($matches['file']);
                $message->setLine($matches['line']);
                $message->setChar($matches['col']);
                $message->setCode($matches['error']);

                if ($matches['severity'] === 'error') {
                    $message->setSeverity(
                        ArcanistLintSeverity::SEVERITY_ERROR);
                } else {
                    $message->setSeverity(
                        ArcanistLintSeverity::SEVERITY_WARNING);
                }

                $message->setName('OCLINT.' . $matches['error']);

                $messages[] = $message;
            }
        }
        return $messages;
    }

    public function lintPath($path) {
        list($err, $stdout) = exec_manual('which oclint');
        if ($err) {
            throw new ArcanistUsageException("OCLint does not appear to be "
                ."available on the path. Make sure that the OCLint is "
                ."installed.");
        }

        $pathOnDisk = $this->getEngine()->getFilePathOnDisk($path);
        $currentDirectory = dirname($pathOnDisk);
        $ocLintPath = strstr($stdout, "\n", true);

        try {
            $stdout = array();
            $_ = 0;
            $ocLintOpts = implode(' ', $this->getOCLintOpts());
            $compilerOpts = implode(' ', $this->getCompilerOpts());
            exec("$ocLintPath $ocLintOpts $pathOnDisk -- $compilerOpts 2>&1", $stdout, $_);
        } catch (CommandException $e) {
            $stdout = $e->getStdout();
        }

        $messages = $this->parseOCLintOutput($stdout);
        foreach ($messages as $message) {
            $this->addLintMessage($message);
        }
    }
}
