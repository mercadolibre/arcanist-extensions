<?php

final class ArcanistSassLinter extends ArcanistExternalLinter {

    public function getDefaultBinary() {
        $config = $this->getEngine()->getConfigurationManager();
        return $config->getConfigFromAnySource('lint.sass.bin', 'scss-lint');
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

    public function getLinterConfigurationOptions() {
        $options = parent::getLinterConfigurationOptions();

        $options['paths'] = array(
            'type' => 'optional list<string>',
            'help' => 'An optional list of paths to be checked'
        );

        return $options;
    }

    protected function parseLinterOutput($path, $err, $stdout, $stderr) {
        $messages = array();
        $report_dom = new DOMDocument();

        $ok = $report_dom->loadXML($stdout);
        if (!$ok) {
            return false;
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
                $message->setLine($child->getAttribute('line'));
                $message->setChar($child->getAttribute('column'));
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
