<?php

class CheckstyleGradleLintProvider extends DefaultLintProvider implements GradleLintProvider {

  public function getName() {
    return 'checkstyle';
  }

  public function getTargets() {
    return array('checkstyle');
  }

  public function parseLinterOutput(array $paths) {
    $parser = new CheckstyleParser();
    return $parser->parseAll(
      '/build\/reports\/checkstyle\/checkstyle.*\.xml$/i', $paths);
  }

  public function isLintDetectedMessage($error_message) {
    return strpos($error_message, 'Checkstyle rule violations were found.') !== false;
  }
}
