<?php
/**
 *
 */

final class GradleUnitTestEngine extends AbstractXUnitTestEngine {

    protected function findTestXmlFiles() {
        $base = getcwd();
        $directory = new RecursiveDirectoryIterator($base);
        $iterator = new RecursiveIteratorIterator($directory);
        $regex = new RegexIterator($iterator,
            '/^.+build\/test-results\/.*\/TEST.*\.xml$/i',
            RecursiveRegexIterator::GET_MATCH);
        $matches = iterator_to_array($regex);
        $files = array();
        foreach ($matches as $match) {
            $files[] = $match[0];
        }
        if (!count($files)) {
            throw new ArcanistUsageException('Could not find any test output '
                .'files. Check this project is correctly configured and '
                .'actually a Gradle project.');
        }
        return $files;
    }

    public function getDefaultBinary() {
        $config = $this->getConfigurationManager();
        return $config->getConfigFromAnySource('bin.gradle', 'gradle');
    }

    public function getMandatoryFlags() {
        return array('test', '--daemon');
    }

    public function getDefaultFlags() {
        $config = $this->getConfigurationManager();
        return $config->getConfigFromAnySource('unit.gradle.options', array());
    }

}
