<?php

class FindbugsGradleLintProvider implements GradleLintProvider {

  public function getName() {
    return 'findbugs';
  }

  public function getTargets() {
    return array('build');
  }

  public function parseLinterOutput(array $paths) {
    $parser = new FindbugsParser();
    return $parser->parseAll(
      '/build\/reports\/findbugs\/findbugs\.xml$/i', $paths);
  }
}
