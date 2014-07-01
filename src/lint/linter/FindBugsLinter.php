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
        return $messages;
    }

    public function getMandatoryFlags() {
        return array(
            'compile',
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
       if (!count($files)) {
            throw new ArcanistUsageException('Could not find any findBug output files. '
                . 'Check this project is correctly configured and actually a Java project.');
       }
       return $files;
    }

    private function parseReport($report, $files) {
        $report_dom = new DOMDocument();
        $content = file_get_contents($report);

        if (!$content) {
            throw new ArcanistUsageException('The linter failed to produce a '
                . 'meaningful output. This might be due to reading an empty '
                . 'file. Try running `mvn clean` and then this linter again.');
        }

        $ok = $report_dom->loadXML($content);
        if (!$ok) {
            throw new ArcanistUsageException('Arcanist failed to understand the '
                . 'linter output. Aborting.');
        }

        $bugs = $report_dom->getElementsByTagName('BugInstance');
        $base = $report_dom->getElementsByTagName('SrcDir')->item(0)->nodeValue;

        $messages = array();
        foreach ($bugs as $bug) {
            $description = $bug->getElementsByTagName('LongMessage');
            $description = $description->item(0);
            $description = $description->nodeValue;
            $sourceline = $bug->getElementsByTagName('SourceLine')->item(0);

            $severity = $bug->getAttribute('priority');
            if ($severity >= 5) {
                $prefix = 'E';
            } else {
                $prefix = 'W';
            }

            $code = 'FB.'.$prefix.'.'.$bug->getAttribute('type');

            $message = new ArcanistLintMessage();
            $sourcePath = $sourceline->getAttribute('sourcepath');
            $message->setPath($base . '/' . $sourcePath);
            $message->setCode($code);
            $message->setDescription($description);
            $message->setSeverity($this->getLintMessageSeverity($code));

            // do we have a start line?
            $line = $sourceline->getAttribute('start');
            if ($line != "") {
                $message->setLine(intval($line));
            }

            // skip files not in diff/changed
            $curPath = $sourceline->getAttribute('sourcepath');
            foreach ($files as $file) {
                if (!strcmp(realpath($file), realpath($base . '/' . $curPath))) {
                    $messages[] = $message;
                }
            }
        }

        return $messages;
    }
    
    protected function getDefaultMessageSeverity($code) {
        if (substr($code, 5) == "FB.W.") {
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
            $newMessages = $this->parseReport($report, $paths);
            $messages += $newMessages;
        }
        return $messages;
    }

}
