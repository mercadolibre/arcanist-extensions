<?php

final class OCLintLinter extends ArcanistCommandLinter {

    private $didGenerateCompileCommands = false;

    public function getInfoDescription() {
        return 'This uses xcodebuild, xcpretty and oclint.'.PHP_EOL
            .'It assumes you use cocoapods for project organization. You can configure the following keys:'.PHP_EOL
            .XcodebuildConfiguration::getOptionsDescription();
    }

    protected function getDefaultBinary() {
        $config = $this->getEngine()->getConfigurationManager();
        return $config->getConfigFromAnySource('bin.oclint', 'oclint');
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
        return $config->getConfigFromAnySource('lint.oclint.options', array('-enable-clang-static-analyzer'));
    }

    protected function prepareToLint() {
        $configuration_manager = $this->getEngine()->getConfigurationManager();
        $configuration = new XcodebuildConfiguration($configuration_manager);

        $build_flags = array('-dry-run', 'clean', 'build');
        $result = exec_manual('set -o pipefail && '
            .$configuration->buildCommand($build_flags)
            .'| %s '
            .'--report json-compilation-database '
            .'--output compile_commands.json ',
            $this->getXCPrettyPath());

        if ($result[0]) {
            throw new Exception('failed executing xcodebuild'
                .PHP_EOL.$result[2]);
        }
    }

    protected function parseLinterOutput($err, $stdout, $stderr) {
        $compiler_regexp = '/(?<file>[^:]+):(?P<line>\d+):(?P<col>\d+): (?<desc>.*)/is';
        $error_regexp = '/(?<file>[^:]+):(?P<line>\d+):(?P<col>\d+):'
            .' (?<name>.*) \[(?<category>[^|]+)\|(?<priority>P[0-9])\](?<desc>.*)/is';
        $messages = array();

        if ($stdout === '') {
            return $messages;
        }

        $clang_severity = ArcanistLintSeverity::SEVERITY_ERROR;
        $clang_source = 'CLANG';

        foreach (explode("\n", $stdout) as $line) {
            $matches = array();
            // The order for matching the regexps is important, since the compiler regexp
            // always matches against lint errors, but not the other way round.
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
            } else if ($c = preg_match($compiler_regexp, $line, $matches)) {
                $message = new ArcanistLintMessage();
                $message->setPath($matches['file']);
                $message->setLine((int)$matches['line']);
                $message->setChar((int)$matches['col']);
                $message->setDescription($matches['desc']);

                $message->setSeverity($clang_severity);

                if ($clang_severity == ArcanistLintSeverity::SEVERITY_ERROR) {
                    $message->setCode($clang_source.'.ERROR');
                    $message->setName('Compiler error');
                } else {
                    $message->setCode($clang_source.'.WARNING');
                    $message->setName('Compiler warning');
                }

                $messages[] = $message;
            } else if (strpos($line, 'Compiler Warnings:') !== false) {
                $clang_severity = ArcanistLintSeverity::SEVERITY_WARNING;
                $clang_source = 'CLANG';
            } else if (strpos($line, 'Compiler Errors:') !== false) {
                $clang_severity = ArcanistLintSeverity::SEVERITY_ERROR;
                $clang_source = 'CLANG';
            } else if (strpos($line, 'Clang Static Analyzer Results:') !== false) {
                $clang_severity = ArcanistLintSeverity::SEVERITY_WARNING;
                $clang_source = 'ANALYZER';
            }


        }
        return $messages;
    }

    public function lintPath($path) {
        $result = exec_manual($this->buildCommand(array($path)));
        $messages = $this->parseLinterOutput($result[0], $result[1], $result[2]);

        foreach ($messages as $message) {
            $this->addLintMessage($message);
        }

        // OCLint generates a .plist with the results from the clang static
        // analyzer. It's not useful at all, so we delete it.
        $plist = basename($path, '.m').'.plist';
        if (file_exists($plist)) {
            unlink($plist);
        }
    }

    public function willLintPaths(array $paths) {
        parent::willLintPaths($paths);

        // The engine will attempt to run the linter on chunks of paths,
        // but we will ignore that and run for ALL paths, so we need to
        // make sure to run just once.
        if ($this->didGenerateCompileCommands) {
            return;
        }
        $this->didGenerateCompileCommands = true;

        list($err, $stdout) = exec_manual('which %s', $this->getDefaultBinary());
        if ($err) {
            throw new ArcanistUsageException("can't find oclint");
        }

        $working_copy = $this->getEngine()->getWorkingCopy();
        $root = $working_copy->getProjectRoot();
        chdir($root);

        $this->prepareToLint();
    }

}
