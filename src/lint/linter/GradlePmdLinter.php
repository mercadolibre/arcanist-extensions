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
}
