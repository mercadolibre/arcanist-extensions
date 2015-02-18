<?php

class PmdMavenLintProvider implements MavenLintProvider {

  public function getName() {
    return 'pmd';
  }

  public function getTargets() {
    return array('pmd:pmd');
  }

  public function getAdditionalFlags() {
    return array();
  }

  public function getPathArgument(array $src_paths, array $lint_paths) {
    return ''; // Can't do this with params :(
  }

  public function parseLinterOutput(array $paths) {
    $parser = new PmdParser();
    return $parser->parseAll('/target\/pmd\.xml$/i', $paths);
  }
}
