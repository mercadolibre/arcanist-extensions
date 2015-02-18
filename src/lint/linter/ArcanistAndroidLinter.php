<?php

/*

Copyright 2012-2014 iMobile3, LLC. All rights reserved.
Copyright 2014 Monits SA. All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, is permitted provided that adherence to the following
conditions is maintained. If you do not agree with these terms,
please do not use, install, modify or redistribute this software.

1. Redistributions of source code must retain the above copyright notice, this
list of conditions and the following disclaimer.

2. Redistributions in binary form must reproduce the above copyright notice,
this list of conditions and the following disclaimer in the documentation
and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY IMOBILE3, LLC "AS IS" AND ANY EXPRESS OR
IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO
EVENT SHALL IMOBILE3, LLC OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT,
INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF
LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE
OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF
ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

*/

/**
 * Uses Android Lint to detect various errors in Java code. To use this linter,
 * you must install the Android SDK and configure which codes you want to be
 * reported as errors, warnings and advice.
 *
 * @group linter
 */
final class ArcanistAndroidLinter extends ArcanistLinter {
    private $_gradleModules;

    public function getDefaultBinary() {
        $ANDROID_HOME = getenv('ANDROID_HOME');
        if (!$ANDROID_HOME) {
            throw new ArcanistUsageException('$ANDROID_HOME does not seem to '
            . 'be set up. Your android sdk installation might be incomplete, '
            . 'missing or outright broken.');
        }
        return $ANDROID_HOME . '/tools/lint';
    }

    private function getLintPath() {
        $lint_bin = $this->getDefaultBinary();

        list($err, $stdout) = exec_manual('which %s', $lint_bin);
        if ($err) {
            throw new ArcanistUsageException('Lint does not appear to be '
            . 'available on the path. Make sure that the Android tools '
            . 'directory is part of your path.');
        }

        return trim($stdout);
    }

    private function getGradlePath() {
        $gradle_bin = 'gradle';

        list($err, $stdout) = exec_manual('which %s', $gradle_bin);
        if ($err) {
            throw new ArcanistUsageException('Gradle does not appear to be '
                .'available on the path.');
        }

        return trim($stdout);
    }

    public function getLinterConfigurationOptions() {
        $options = parent::getLinterConfigurationOptions();

        $options['gradle-modules'] = array(
            'type' => 'optional list<string>',
            'help' => 'An optional list of the name of the gradle modules'
                . ' to be analyzed. This is needed only if you are using a'
                . ' Gradle project.'
        );

        return $options;
    }

    public function setLinterConfigurationValue($key, $value) {
        if ($key === 'gradle-modules') {
            $this->_gradleModules = $value;
        }
    }

    private function constructPaths($paths) {
        $fullPaths = array();
        foreach ($paths as $path) {
            $fullPaths[] = $this->getEngine()->getFilePathOnDisk($path);
        }
        return $fullPaths;
    }

    private function runLint($paths) {
        $lint_bin = $this->getLintPath();
        $arc_lint_location = tempnam(sys_get_temp_dir(), 'arclint.xml');

        $fullPaths = $this->constructPaths($paths);
        $cmd = (string) csprintf(
            '%C --showall --nolines --fullpath --quiet --xml %s %Ls',
            $lint_bin, $arc_lint_location, $fullPaths);
        list($err, $stdout, $stderr) = exec_manual($cmd);
        if ($err != 0 && $err != 1) {
            throw new ArcanistUsageException('Error executing lint '
                . "command:\n" . $stdout . "\n\n" . $stderr);
        }

        return array($arc_lint_location);
    }

    /*
     * When using Gradle we lint the whole project,
     * we can't specify just a few files
    */
    private function runGradle($paths) {
        $project_root = $this->getEngine()->getWorkingCopy()->getProjectRoot();
        $gradle_bin = join('/', array($project_root, 'gradlew'));
        if (!file_exists($gradle_bin)) {
            $gradle_bin = $this->getGradlePath();
        }
        $cwd = getcwd();
        chdir($project_root);
        $lint_command = '';
        $output_paths = array();
        foreach ($this->_gradleModules as $module) {
            $lint_command .= ':' . $module . ':lint ';
            $output_paths[] = $project_root . '/' . $module
                . '/build/outputs/lint-results.xml';
        }
        list($err) = exec_manual($gradle_bin . ' ' . $lint_command);
        chdir($cwd);
        if ($err) {
            throw new ArcanistUsageException('Error executing gradle command');
        }
        return $output_paths;
    }

    public function willLintPaths(array $paths) {
        return $this->lintPaths($paths);
    }

    public function getLinterConfigurationName() {
        return 'AndroidLint';
    }

    public function getLinterName() {
        return 'AndroidLint';
    }

    public function getLintSeverityMap() {
        return array();
    }

    public function getLintNameMap() {
        return array();
    }

    protected function shouldLintDirectories() {
        return true;
    }

    protected function parseOutputXML($filexml) {
        $messages = array();
        foreach ($filexml as $issue) {
            $loc_attrs = $issue->location->attributes();
            $issue_attrs = $issue->attributes();

            $message = new ArcanistLintMessage();
            $message->setPath((string)$loc_attrs->file);
            // Line number and column are irrelevant for
            // artwork and other assets
            if (isset($loc_attrs->line)) {
                $message->setLine(intval($loc_attrs->line));
            }
            if (isset($loc_attrs->column)) {
                $message->setChar(intval($loc_attrs->column));
            }
            $message->setName((string)$issue_attrs->id);
            $message->setCode('AND.' . (string)$issue_attrs->category);
            $message->setDescription(preg_replace('/^\[.*?\]\s*/', '',
                $issue_attrs->message));

            // Setting Severity
            if ($issue_attrs->severity == 'Error'
                    || $issue_attrs->severity == 'Fatal'
            ) {
                $message->setSeverity(
                    ArcanistLintSeverity::SEVERITY_ERROR);
            } else if ($issue_attrs->severity == 'Warning') {
                $message->setSeverity(
                    ArcanistLintSeverity::SEVERITY_WARNING);
            } else {
                $message->setSeverity(
                    ArcanistLintSeverity::SEVERITY_ADVICE);
            }

            $messages[] = $message;
        }

        return $messages;
    }

    // Try to lint all the paths at once. We still need to implement this
    // method, though.
    public function lintPath($path) {}

    public function lintPaths($paths) {
        $lint_bin = $this->getLintPath();

        if (!empty($this->_gradleModules)) {
            $arc_lint_locations = $this->runGradle($paths);

            // This linter is deprecated, warn the user
            $message = new ArcanistLintMessage();
            $message->setCode('AND.DEPRECATED');
            $message->setDescription('This linter is deprecated, switch to the'
                . ' new "gradle" linter using the "android" provider.');
            $message->setSeverity(ArcanistLintSeverity::SEVERITY_ERROR);

            $this->addLintMessage($message);
        } else {
            $arc_lint_locations = $this->runLint($paths);
        }
        foreach ($arc_lint_locations as $arc_lint_location) {
            $contents = file_get_contents($arc_lint_location);

            if (!$contents) {
                throw new ArcanistUsageException('The linter returned an empty'
                . ' response. This usually means something went wrong.'
                . ' Aborting.');
            }

            $filexml = simplexml_load_string($contents);

            if ($filexml->attributes()->format < 4) {
                throw new ArcanistUsageException('Unsupported Android lint'
                . ' output version. Please update your Android SDK to the'
                . ' latest version.');
            } else if ($filexml->attributes()->format > 4) {
                throw new ArcanistUsageException('Unsupported Android lint'
                . ' output version. Arc Lint needs an update to match.');
            }

            $messages = $this->parseOutputXML($filexml);

            foreach ($messages as $message) {
                $this->addLintMessage($message);
            }

            unlink($arc_lint_location);
        }
    }
}
