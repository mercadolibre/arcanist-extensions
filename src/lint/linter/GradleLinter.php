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

  protected function isCompileError($status, $stdout, $stderr) {
    if ($status === 0) {
      return false;
    }

    // Since we are using --continue we have to check if all "failures" are down to linters finding errors
    $regex = "/Execution failed for task '(?P<taskname>[^']+)[^>]*>(?P<error>[\s\S]*?(?=\* Try))/";
    $matches = array();

    if (preg_match_all($regex, $stderr, $matches, PREG_SET_ORDER)) {
      foreach ($matches as $match) {
        $is_error = true;

        // Does any linter recognize the failing task as it's own?
        foreach ($this->_linters as $linter) {
          if ($this->isLinterTask($linter, $match['taskname'])) {
            if ($linter->isLintDetectedMessage($match['error'])) {
              $is_error = false;
            }
            break;
          }
        }

        if ($is_error) {
          return true;
        }
      }
    }

    return false;
  }

  private function isLinterTask($linter, $task_name) {
    foreach ($linter->getTargets() as $target) {
      // It may not be an exact match, ie: :findbugsMain vs :findbugs
      if (strpos($task_name, ":$target") !== false) {
        return true;
      }
    }

    return false;
  }
}
