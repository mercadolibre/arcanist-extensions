<?php

final class ArcanistFindBugsLinter extends ArcanistLinter {

    public function getLinterName() {
        return 'findbugs';
    }

    public function getLinterConfigurationName() {
        return 'findbugs';
    }

    final public function lintPath($path) {
    }

    final public function willLintPaths(array $paths) {
        $result = exec_manual($this->buildCommand($paths));
        $messages = $this->parseLinterOutput($paths, $result[0], $result[1], $result[2]);

        foreach ($messages as $message) {
            $this->addLintMessage($message);
        }
    }

    public function getMandatoryFlags() {
        return array(
            'findbugs:findbugs'
        );
    }

    public function getDefaultFlags() {
        $config = $this->getEngine()->getConfigurationManager();
        return $config->getConfigFromAnySource('lint.findbugs.options', array());
    }

    public function getDefaultBinary() {
        $config = $this->getEngine()->getConfigurationManager();
        return $config->getConfigFromAnySource('lint.findbugs.bin', 'mvn');
    }

    protected function findFindBugsXmlFiles() {
       $base = getcwd();
       $Directory = new RecursiveDirectoryIterator($base);
       $Iterator = new RecursiveIteratorIterator($Directory);
       $Regex = new RegexIterator($Iterator, '/^.+target\/findbugsXml\.xml$/i', RecursiveRegexIterator::GET_MATCH);
       $matches = iterator_to_array($Regex);
       $files = array();
       foreach ($matches as $match) {
           $files[] = $match[0];
       }
       return $files;
    }

    private function parseReport($report) {
        $report_dom = new DOMDocument();
        $content = file_get_contents($report);

        $ok = $report_dom->loadXML($content);
        if (!$ok) {
            return false;
        }

        $bugs = $report_dom->getElementsByTagName('BugInstance');
        $messages = array();
        foreach ($bugs as $bug) {

                $description = $bug->getElementsByTagName('ShortMessage');
                $description = $description->item(0);
                $description = $description->nodeValue;

                $sourceline = $bug->getElementsByTagName('SourceLine')->item(0);

                $severity = $bug->getAttribute('priority');
                if ($severity >= 5) {
                    $prefix = 'E';
                } else {
                    $prefix = 'W';
                }

                $code = 'FB.'.$prefix.'.'.$bug->getAttribute('abbrev');

                $message = new ArcanistLintMessage();
                $message->setPath($sourceline->getAttribute('sourcepath'));
                $message->setLine($sourceline->getAttribute('start'));
                $message->setChar('0');
                $message->setCode($code);
                $message->setDescription($description);
                $message->setSeverity($this->getLintMessageSeverity($code));

                $messages[] = $message;
        }

        return $messages;
    }
    
    protected function getDefaultMessageSeverity($code) {
        if (preg_match('/^FB\\.W\\./', $code)) {
            return ArcanistLintSeverity::SEVERITY_WARNING;
        } else {
            return ArcanistLintSeverity::SEVERITY_ERROR;
        }
    }

    protected function buildCommand($paths) {
        $binary = $this->getDefaultBinary();
        $mandatoryArgs = $this->getMandatoryFlags();
        $defaultArgs = $this->getDefaultFlags();
        return (string) csprintf('%C %LR %LR %Ls', $binary, $mandatoryArgs, $defaultArgs, $paths);
    }

    protected function parseLinterOutput($paths, $err, $stdout, $stderr) {
        $messages = array();

        $reports = $this->findFindBugsXmlFiles();
        foreach ($reports as $report) {
            printf("Linting report %s\n", $report);    
            $newMessages = $this->parseReport($report);
            $messages += $newMessages;
        } 
        return $messages;
    }

}
