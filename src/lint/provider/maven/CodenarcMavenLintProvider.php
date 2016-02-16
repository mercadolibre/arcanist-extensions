<?php

class CodenarcMavenLintProvider extends DefaultLintProvider implements MavenLintProvider {

  public function getName() {
    return 'codenarc';
  }

  public function getTargets() {
    return array('codenarc:codenarc');
  }

  public function getAdditionalFlags() {
    return array();
  }

  public function getPathArgument(array $src_paths, array $lint_paths) {
    foreach ($lint_paths as $key => $value) {
      $lint_paths[$key] = '**/'.$value;
    }

    return sprintf('-Dcodenarc.includes="%s"', implode(',', $lint_paths));
  }

  public function parseLinterOutput(array $paths) {
    $parser = new CodenarcParser();
    return $parser->parseAll('/\Codenarc\.xml$/i', $paths);
  }
}
