<?php

class ArcanistFindBugsLinter extends ArcanistSingleRunLinter {
    protected $_findbugsXmlRegEx = '/^.+target\/findbugsXml\.xml$/i';

    public function getLinterName() {
        return 'findbugs';
    }

    public function getLinterConfigurationName() {
        return 'findbugs';
    }

    public function getMandatoryFlags() {
        return array(
            'compile',
            'compiler:testCompile',
            'findbugs:findbugs',
            '-Dfindbugs.includeTests=true'
        );
    }

    protected function extractRelativeFilePath($path) {
        $srcPaths = array(
            'src/main/java',
            'src/test/java'
        );

        foreach ($srcPaths as $prefix) {
            $idx = strpos($path, $prefix);
            if ($idx !== false) {
                $relative_path = substr($path, $idx + strlen($prefix));
                if (0 === strpos($relative_path, '/')) {
                    $relative_path = substr($relative_path, 1);
                }

                return $relative_path;
            }
        }
    }

    protected function getPathsArgumentForLinter($paths) {
        $classNames = array();
        foreach ($paths as $path) {
            $relativePath = $this->extractRelativeFilePath($path);
            $class = str_replace(
                '/', '.', preg_replace('/\.java$/', '', $relativePath));
            $classNames[] = $class;
            /*
             * Let the hackish begin...
             *
             * We also want to analyze inner classes. Findbugs has no support
             * to do this directly, it can either get a specific class, or
             * a package. And we have no idea by the path of what inner
             * classes there may be... So we abuse the source!
             *  https://code.google.com/p/findbugs/source/browse/findbugs/src/java/edu/umd/cs/findbugs/ClassScreener.java?name=master
             * Since the incoming param is not strictly quoted,
             * we can use SOME RegExp expressions, and that's exactly what
             * we do here!
            */
            $classNames[] = $class . '\\$\\\\S+';
        }
        $classes = implode(',', $classNames);
        return sprintf('-Dfindbugs.onlyAnalyze="%s"', $classes);
    }

    public function getDefaultFlags() {
        $config = $this->getEngine()->getConfigurationManager();
        return $config->getConfigFromAnySource(
            'lint.findbugs.options', array());
    }

    public function getDefaultBinary() {
        $config = $this->getEngine()->getConfigurationManager();
        return $config->getConfigFromAnySource('lint.findbugs.bin', 'mvn');
    }

    protected function findFindBugsXmlFiles() {
       $base = getcwd();
       $Directory = new RecursiveDirectoryIterator($base);
       $Iterator = new RecursiveIteratorIterator($Directory);
       $Regex = new RegexIterator($Iterator, $this->_findbugsXmlRegEx,
           RecursiveRegexIterator::GET_MATCH);
       $matches = iterator_to_array($Regex);
       $files = array();
       foreach ($matches as $match) {
           $files[] = $match[0];
       }
       if (!count($files)) {
            throw new ArcanistUsageException('Could not find any findbugs'
                . ' output files. Check this project is correctly configured'
                . ' and actually a Java project.');
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
            throw new ArcanistUsageException('Arcanist failed to understand the'
                . ' linter output. Aborting.');
        }

        $bugs = $report_dom->getElementsByTagName('BugInstance');
        $base = $report_dom->getElementsByTagName('SrcDir')->item(0)->nodeValue;

        $messages = array();
        foreach ($bugs as $bug) {
            $description = $bug->getElementsByTagName('LongMessage');
            $description = $description->item(0);
            $description = $description->nodeValue;
            $sourcelineList = $bug->getElementsByTagName('SourceLine');
            $sourceline = $sourcelineList->item($sourcelineList->length - 1);

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
                if (!strcmp(realpath($file),
                        realpath($base . '/' . $curPath))) {
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

        $reports = $this->findFindBugsXmlFiles();
        foreach ($reports as $report) {
            $newMessages = $this->parseReport($report, $paths);
            $messages = array_merge($messages, $newMessages);
        }
        return $messages;
    }

}
