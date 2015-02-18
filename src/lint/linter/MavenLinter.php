<?php

class MavenLinter extends AbstractMetaLinter {
  private $_srcPaths;

  public function __construct() {
    parent::__construct('MavenLintProvider');

    // Default paths for a java project
    $this->_srcPaths = array(
      'src/main/java',
      'src/test/java'
    );
  }

  public function getLinterName() {
    return 'maven';
  }

  public function getLinterConfigurationName() {
    return 'maven';
  }

  public function getLinterConfigurationOptions() {
    $options = parent::getLinterConfigurationOptions();

    $options['paths'] = array(
      'type' => 'optional list<string>',
      'help' => 'An optional list of the build paths used by this project. '
        . 'This is needed only if you don\'t use a standard layout '
        . 'or are not using Java.'
    );

    return $options;
  }

  public function getInstallInstructions() {
    return pht('Have a pom.xml in the root configuring every plugin'
      . ' to be used for linting.');
  }

  public function getDefaultBinary() {
    $config = $this->getEngine()->getConfigurationManager();
    return $config->getConfigFromAnySource('bin.maven', 'mvn');
  }

  public function setLinterConfigurationValue($key, $value) {
    if ($key === 'paths') {
      $this->_srcPaths = $value;
    } else {
      parent::setLinterConfigurationValue($key, $value);
    }
  }

  public function getMandatoryFlags() {
    $flags = parent::getMandatoryFlags();

    foreach ($this->_linters as $linter) {
      $flags = array_merge($flags, $linter->getAdditionalFlags());
    }

    return $flags;
  }

  protected function getPathsArgumentForLinter($paths) {
    $args = array();
    foreach ($this->_linters as $linter) {
      $args[] = $linter->getPathArgument($this->_srcPaths, $paths);
    }

    return implode(' ', $args);
  }
}
