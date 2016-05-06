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
      .' to be used for linting.');
  }

  public function getDefaultBinary() {
    $config = $this->getEngine()->getConfigurationManager();
    /*
     * Single run linters are guaranteed to be run from project root,
     * so using ./ is ok. See ArcanistSingleRunLinter.
    */
    return $config->getConfigFromAnySource('bin.gradle', './gradlew');
  }

  protected function getPathsArgumentForLinter($paths) {
    return '';
  }

  public function getMandatoryFlags() {
    $flags = parent::getMandatoryFlags();
    $flags[] = '--daemon';
    $flags[] = '--continue'; // in case any lint task is configured to fail on error

    return array_unique($flags); // just in case a provider set it (shouldn't)
  }
}
