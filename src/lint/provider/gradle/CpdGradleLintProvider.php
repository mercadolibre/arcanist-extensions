<?php

class CpdGradleLintProvider implements GradleLintProvider {

  public function getName() {
    return 'cpd';
  }

  public function getTargets() {
    return array('build');
  }

  public function parseLinterOutput(array $paths) {
    $parser = new CpdParser();
    return $parser->parseAll(
      '/build\/reports\/pmd\/cpd\.xml$/i', $paths);
  }
}
