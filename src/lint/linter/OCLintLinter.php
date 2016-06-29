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

    protected function parseLinterOutput($paths, $err, $stdout, $stderr) {
        $compiler_regexp = '/(?<file>[^:]+):(?P<line>\d+):(?P<col>\d+): (?<desc>.*)/is';
        $error_regexp = '/(?<file>[^:]+):(?P<line>\d+):(?P<col>\d+):'
            .' (?<name>.*) \[(?<category>[^|]+)\|(?<priority>P[0-9])\](?<desc>.*)/is';
        $messages = array();

        if ($stdout === '') {
            return $messages;
        }

        $clang_severity = ArcanistLintSeverity::SEVERITY_ERROR;

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
                    $message->setCode('CLANG.ERROR');
                    $message->setName('Compiler error');
                } else {
                    $message->setCode('CLANG.WARNING');
                    $message->setName('Compiler warning');
                }

                $messages[] = $message;
            } else if (strpos($line, 'Compiler Warnings:') !== false) {
                $clang_severity = ArcanistLintSeverity::SEVERITY_WARNING;
            } else if (strpos($line, 'Compiler Errors:') !== false) {
                $clang_severity = ArcanistLintSeverity::SEVERITY_ERROR;
            }


        }
        return $messages;
    }

}
