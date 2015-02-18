<?php

class AndroidGradleLintProvider implements GradleLintProvider {

  public function getName() {
    return 'android';
  }

  public function getTargets() {
    return array('build');
  }

  public function parseLinterOutput(array $paths) {
    $parser = new AndroidParser();
    return $parser->parseAll(
      '/build\/outputs\/lint-results\.xml$/i', $paths);
  }
}
