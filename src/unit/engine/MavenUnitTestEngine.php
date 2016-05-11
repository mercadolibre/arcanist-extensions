<?php

/**
 *
 */

final class MavenUnitTestEngine extends AbstractXUnitTestEngine {

    protected function findTestXmlFiles() {
        $base = getcwd();
        $directory = new RecursiveDirectoryIterator($base,
            FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO
            | FilesystemIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator($directory);
        $regex = new RegexIterator($iterator,
            '/^.+target\/surefire-reports\/TEST.*\.xml$/i',
            RecursiveRegexIterator::GET_MATCH);
        $matches = iterator_to_array($regex);
        $files = array();
        foreach ($matches as $match) {
            $files[] = $match[0];
        }
        if (!count($files)) {
            throw new ArcanistUsageException('Could not find any test output '
                .'files. Check this project is correctly configured and '
                .'actually a Java project.');
        }
        return $files;
    }

    public function getDefaultBinary() {
        $config = $this->getConfigurationManager();
        return $config->getConfigFromAnySource('bin.maven', 'mvn');
    }

    public function getMandatoryFlags() {
        return array('clean', 'test', '-Dsurefire.useFile=true');
    }

    public function getDefaultFlags() {
        $config = $this->getConfigurationManager();
        return $config->getConfigFromAnySource('unit.maven.options', array());
    }

}
