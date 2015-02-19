<?php

class GradleLinter extends AbstractMetaLinter {

  public function __construct() {
    parent::__construct('GradleLintProvider');
  }

  public function getLinterName() {
    return 'gradle';
  }

  public function getLinterConfigurationName() {
    return 'gradle';
  }

  public function getInstallInstructions() {
    return pht('Have a build.gradle in the root configuring every plugin'
      . ' to be used for linting.');
  }

  public function getDefaultBinary() {
    $config = $this->getEngine()->getConfigurationManager();
    return $config->getConfigFromAnySource('bin.gradle', 'gradle');
  }

  protected function getPathsArgumentForLinter($paths) {
    return '';
  }

  public function getMandatoryFlags() {
    $flags = parent::getMandatoryFlags();
    $flags[] = '--daemon';

    return array_unique($flags); // just in case a provider set it (shouldn't)
  }
}
