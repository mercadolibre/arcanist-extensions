<?php

final class ArcanistSassLinter extends ConfigPathLinter {

    public function getDefaultBinary() {
        $config = $this->getEngine()->getConfigurationManager();
        return $config->getConfigFromAnySource('bin.sass', 'scss-lint');
    }

    public function getMandatoryFlags() {
        return array(
            '-f', 'XML'
        );
    }

    public function getInstallInstructions() {
        return pht('run sudo gem installscss-lint.');
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
        $messages = array();
        $report_dom = new DOMDocument();

        if (!$stdout) {
            throw new ArcanistUsageException('The linter produced no output. '
                . 'This might be a bug, so I\'m showing you the stderr below:'
                . "\n" . $stderr);
        }

        $ok = $report_dom->loadXML($stdout);
        if (!$ok) {
            throw new ArcanistUsageException('The linter produced no parseable '
                . 'output. This might be a bug, so I\'m showing you '
                . 'the stderr below:'
                . "\n" . $stderr);
        }

        $files = $report_dom->getElementsByTagName('file');
        foreach ($files as $file) {
            foreach ($file->childNodes as $child) {
                if (!($child instanceof DOMElement)) {
                    continue;
                }

                $severity = $child->getAttribute('severity');
                if ($severity == 'error') {
                    $prefix = 'E';
                } else {
                    $prefix = 'W';
                }

                $code = 'SASS.'.$prefix.'.'.$child->getAttribute('reason');

                $message = new ArcanistLintMessage();
                $message->setPath($path);
                $message->setLine(intval($child->getAttribute('line')));
                $message->setChar(intval($child->getAttribute('column')));
                $message->setCode($code);
                $message->setDescription($child->getAttribute('reason'));
                $message->setSeverity($this->getLintMessageSeverity($code));

                $messages[] = $message;
            }
        }

        return $messages;
    }

    protected function getDefaultMessageSeverity($code) {
        if (preg_match('/^SASS\\.W\\./', $code)) {
            return ArcanistLintSeverity::SEVERITY_WARNING;
        } else {
            return ArcanistLintSeverity::SEVERITY_ERROR;
        }
    }

}
