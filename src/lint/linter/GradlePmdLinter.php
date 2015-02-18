<?php

class GradlePmdLinter extends PmdLinter {
    protected $_pmdFileRegex = '/^.+build\/reports\/pmd\/pmd\.xml$/i';
    protected $_cpdFileRegex = '/^.+build\/reports\/pmd\/cpd\.xml$/i';

    public function getLinterName() {
        return 'pmd-gradle';
    }

    public function getLinterConfigurationName() {
        return 'pmd-gradle';
    }

    public function getMandatoryFlags() {
        return array(
            'build'
        );
    }

    public function getDefaultFlags() {
        $config = $this->getEngine()->getConfigurationManager();
        return $config->getConfigFromAnySource('lint.pmd.options', array());
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
            . ' new "gradle" linter using the "pmd" provider.');
        /*
         * If you're hackish and you know it, and you really want to show it,
         * clap your hands!
        */
        $messages[1]->setDescription('This linter is deprecated, switch to the'
            . ' new "gradle" linter using the "cpd" provider.');

        return $messages;
    }
}
