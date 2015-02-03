<?php

class PmdLinter extends ArcanistSingleRunLinter {
    protected $_pmdFileRegex = '/^.+target\/pmd\.xml$/i';
    protected $_cpdFileRegex = '/^.+target\/cpd\.xml$/i';

    public function getLinterName() {
        return 'pmd';
    }

    public function getLinterConfigurationName() {
        return 'pmd';
    }

    public function getMandatoryFlags() {
        return array(
            'pmd:pmd',
            'pmd:cpd'
        );
    }

    public function getDefaultFlags() {
        $config = $this->getEngine()->getConfigurationManager();
        return $config->getConfigFromAnySource('lint.pmd.options', array());
    }

    public function getDefaultBinary() {
        $config = $this->getEngine()->getConfigurationManager();
        return $config->getConfigFromAnySource('bin.maven', 'mvn');
    }

    protected function getPathsArgumentForLinter($paths) {
        return '';
    }

    protected function findCpdFiles() {
        return $this->findXmlFiles($this->_cpdFileRegex);
    }

    protected function findPmdFiles() {
        return $this->findXmlFiles($this->_pmdFileRegex);
    }

    protected function findXmlFiles($fileRegex) {
        $base = getcwd();
        $directory = new RecursiveDirectoryIterator($base);
        $iterator = new RecursiveIteratorIterator($directory);
        $regex = new RegexIterator($iterator, $fileRegex,
            RecursiveRegexIterator::GET_MATCH);
        $matches = iterator_to_array($regex);
        $files = array();
        foreach ($matches as $match) {
            $files[] = $match[0];
        }
        if (!count($files)) {
            throw new ArcanistUsageException('Could not find any pmd'
                . ' output files. Check this project is correctly configured'
                . ' and actually a Java project.');
        }
        return $files;
    }

    private function parseCpdReport($report) {
        $report_dom = new DOMDocument();
        $content = file_get_contents($report);

        if (!$content) {
            throw new ArcanistUsageException('The linter failed to produce a '
                . 'meaningful output. This might be due to reading an empty '
                . 'file.');
        }

        $ok = $report_dom->loadXML($content);
        if (!$ok) {
            throw new ArcanistUsageException('Arcanist failed to understand the'
                . ' linter output. Aborting.');
        }

        $duplications = $report_dom->getElementsByTagName('duplication');

        $messages = array();
        foreach ($duplications as $duplicate) {
            $files = $duplicate->getElementsByTagName('file');
            $originalFile = $files->item(0);
            $duplicateFile = $files->item(1);
            $sourcePath = $originalFile->getAttribute('path');

            $description = sprintf('%d lines of code repetead at %s line %d',
                $duplicate->getAttribute('lines'),
                $duplicateFile->getAttribute('path'),
                $duplicateFile->getAttribute('line'));
            $sourceline = $originalFile->getAttribute('line');

            $prefix = 'E';

            $code = 'PMD.'.$prefix.'.CPD';

            $message = new ArcanistLintMessage();
            $message->setPath($sourcePath);
            $message->setCode($code);
            $message->setDescription($description);
            $message->setSeverity($this->getLintMessageSeverity($prefix));
            $message->setLine(intval($sourceline));

            $messages[] = $message;
        }

        return $messages;
    }

    private function parsePmdReport($report) {
        $report_dom = new DOMDocument();
        $content = file_get_contents($report);

        if (!$content) {
            throw new ArcanistUsageException('The linter failed to produce a '
                . 'meaningful output. This might be due to reading an empty '
                . 'file.');
        }

        $ok = $report_dom->loadXML($content);
        if (!$ok) {
            throw new ArcanistUsageException('Arcanist failed to understand the'
                . ' linter output. Aborting.');
        }

        $files = $report_dom->getElementsByTagName('file');

        $messages = array();
        foreach ($files as $file) {
            $violations = $file->getElementsByTagName('violation');
            $sourcePath = $file->getAttribute('name');

            foreach ($violations as $violation) {
                $description = $violation->textContent;
                $sourceline = $violation->getAttribute('beginline');

                $severity = $violation->getAttribute('priority');
                if ($severity < 3) {
                    $prefix = 'E';
                } else {
                    $prefix = 'W';
                }

                $code = 'PMD.'.$prefix.'.'.$violation->getAttribute('rule');

                $message = new ArcanistLintMessage();
                $message->setPath($sourcePath);
                $message->setCode($code);
                $message->setDescription($description);
                $message->setSeverity($this->getLintMessageSeverity($prefix));
                $message->setLine(intval($sourceline));

                $messages[] = $message;
            }
        }

        return $messages;
    }

    protected function getDefaultMessageSeverity($prefix) {
        if ($prefix === 'W') {
            return ArcanistLintSeverity::SEVERITY_WARNING;
        } else {
            return ArcanistLintSeverity::SEVERITY_ERROR;
        }
    }

    protected function parseLinterOutput($paths, $err, $stdout, $stderr) {
        $messages = array();

        if ($err) {
            $message = new ArcanistLintMessage();
            $message->setCode('MVN.COMPILE');
            $message->setDescription('Compilation failed.');
            $message->setSeverity(ArcanistLintSeverity::SEVERITY_ERROR);

            $messages[] = $message;
            return $messages;
        }

        // Pmd files
        $reports = $this->findPmdFiles();
        foreach ($reports as $report) {
            $newMessages = $this->parsePmdReport($report);
            $messages = array_merge($messages, $newMessages);
        }

        // Cpd files
        $reports = $this->findCpdFiles();
        foreach ($reports as $report) {
            $newMessages = $this->parseCpdReport($report);
            $messages = array_merge($messages, $newMessages);
        }

        return $messages;
    }

}
