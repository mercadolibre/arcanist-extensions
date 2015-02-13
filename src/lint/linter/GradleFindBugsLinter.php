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

    protected function parseLinterOutput($paths, $err, $stdout, $stderr) {
        $messages = parent::parseLinterOutput($paths, $err, $stdout, $stderr);

        // Change the deprecation error message

        // If you are hackish and you know it clap your hands!
        $messages[0]->setDescription('This linter is deprecated, switch to the'
            . ' new "gradle" linter using the "findbugs" provider.');

        return $messages;
    }
}
