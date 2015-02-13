<?php

class CheckstyleGradleLintProvider implements GradleLintProvider {

  public function getName() {
    return 'checkstyle';
  }

  public function getTargets() {
    return array('build');
  }

  public function parseLinterOutput(array $paths) {
    $parser = new CheckstyleParser();
    return $parser->parseAll(
      '/build\/reports\/checkstyle\/checkstyle\.xml$/i', $paths);
  }
}
