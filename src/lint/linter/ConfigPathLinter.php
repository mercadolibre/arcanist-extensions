<?php

abstract class ConfigPathLinter extends ArcanistExternalLinter {

    private $configPath;

    public function getDefaultFlags() {
        if ($this->configPath) {
            return array('--config', $this->configPath);
        }
        return array();
    }

    public function setLinterConfigurationValue($key, $value) {
        switch($key) {
        case 'config':
            $this->configPath = $value;
            return;
        }
        return parent::setLinterConfigurationValue($key, $value);
    }

    public function getLinterConfigurationOptions() {
        $options = parent::getLinterConfigurationOptions();

        $options['config'] = array(
            'type' => 'optional string',
            'help' => 'An optional config file'
        );

        return $options;
    }
}
