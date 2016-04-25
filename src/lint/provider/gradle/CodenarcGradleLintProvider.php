<?php

class CodenarcGradleLintProvider extends DefaultLintProvider implements GradleLintProvider {

  public function getName() {
    return 'codenarc';
  }

  public function getTargets() {
    return array('codenarc');
  }

  public function parseLinterOutput(array $paths) {
    $parser = new CodenarcParser();
    return $parser->parseAll(
      '/build\/reports\/codenarc\/.*\.xml$/i', $paths);
  }
}
