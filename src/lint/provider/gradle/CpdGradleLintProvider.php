<?php

class CpdGradleLintProvider extends DefaultLintProvider implements GradleLintProvider {

  public function getName() {
    return 'cpd';
  }

  public function getTargets() {
    return array('cpd');
  }

  public function parseLinterOutput(array $paths) {
    $parser = new CpdParser();
    return $parser->parseAll(
      '/build\/reports\/pmd\/cpd\.xml$/i', $paths);
  }

  public function isLintDetectedMessage($error_message) {
    return strpos($error_message, 'CPD rule violations were found.') !== false;
  }
}
