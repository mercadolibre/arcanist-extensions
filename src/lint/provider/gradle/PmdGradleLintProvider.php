<?php

class PmdGradleLintProvider implements GradleLintProvider {

  public function getName() {
    return 'pmd';
  }

  public function getTargets() {
    return array('build');
  }

  public function parseLinterOutput(array $paths) {
    $parser = new PmdParser();
    return $parser->parseAll(
      '/build\/reports\/pmd\/pmd\.xml$/i', $paths);
  }
}
