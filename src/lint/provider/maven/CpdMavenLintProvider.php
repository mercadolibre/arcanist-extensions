<?php

class CpdMavenLintProvider extends DefaultLintProvider implements MavenLintProvider {

  public function getName() {
    return 'cpd';
  }

  public function getTargets() {
    return array('pmd:cpd');
  }

  public function getAdditionalFlags() {
    return array();
  }

  public function getPathArgument(array $src_paths, array $lint_paths) {
    return ''; // Can't do this with params :(
  }

  public function parseLinterOutput(array $paths) {
    $parser = new CpdParser();
    return $parser->parseAll('/target\/cpd\.xml$/i', $paths);
  }
}
