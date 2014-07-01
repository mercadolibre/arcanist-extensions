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
    
    private function getLintPath() {
        $lint_bin = "lint";

        list($err, $stdout) = exec_manual('which %s', $lint_bin);
        if ($err) {
            throw new ArcanistUsageException("Lint does not appear to be "
            . "available on the path. Make sure that the Android tools "
            . "directory is part of your path.");
        }

        return trim($stdout);
    }

    private function runLint($path) {
        $lint_bin = $this->getLintPath();
        $path_on_disk = $this->getEngine()->getFilePathOnDisk($path);
        $arc_lint_location = tempnam(sys_get_temp_dir(), 'arclint.xml');

        list($err) = exec_manual(
            '%s --showall --nolines --fullpath --quiet --xml %s %s',
            $lint_bin, $arc_lint_location, $path_on_disk);
        if ($err != 0 && $err != 1) {
            throw new ArcanistUsageException("Error executing lint command");
        }

        return $arc_lint_location;
    }

    public function willLintPaths(array $paths) {
        return;
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

    public function lintPath($path) {
        $lint_bin = $this->getLintPath();
        $lint_lib_path = dirname($lint_bin) . '/lib';
        
        $_java_options = getenv('_JAVA_OPTIONS');
        putenv('_JAVA_OPTIONS=-Djava.awt.headless=true');

        $arc_lint_location = $this->runLint($path);
        $contents = file_get_contents($arc_lint_location);

        if (!$contents) {
            throw new ArcanistUsageException('The linter returned an empty '
                . 'response. This usually means something went wrong. Aborting.');
        }

        $filexml = simplexml_load_string($contents);

        if ($filexml->attributes()->format < 4) {
            throw new ArcanistUsageException("Unsupported Android lint output "
            . "version. Please update your Android SDK to the latest "
            . "version.");
        } else if ($filexml->attributes()->format > 4) {
            throw new ArcanistUsageException("Unsupported Android lint output "
            . "version. Arc Lint needs an update to match.");
        }

        $messages = $this->parseOutputXML($filexml);

        foreach ($messages as $message) {
            $this->addLintMessage($message);
        }


        unlink($arc_lint_location);
        putenv("_JAVA_OPTIONS=$_java_options");
    }
}
