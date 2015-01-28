<?php

final class ArcanistGradleFindBugsLinter extends ArcanistFindBugsLinter {

    public function __construct() {
        $this->_findbugsXmlRegEx =
            '/^.+build\/reports\/findbugs\/findbugs\.xml$/i';
    }

    public function getLinterName() {
        return 'findbugs-gradle';
    }

    public function getLinterConfigurationName() {
        return 'findbugs-gradle';
    }

    public function getMandatoryFlags() {
        return array(
            'build'    // check won't work, code may not be compiled
        );
    }

    /*
     * Can't narrow down search in gradle,
     * but can't use Maven's version of this method
    */
    protected function getPathsArgumentForLinter($paths) {
        return '';
    }

    public function getDefaultBinary() {
        $config = $this->getEngine()->getConfigurationManager();
        return $config->getConfigFromAnySource('bin.gradle', 'gradle');
    }
}
