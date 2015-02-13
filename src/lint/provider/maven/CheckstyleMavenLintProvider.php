<?php

class CheckstyleMavenLintProvider implements MavenLintProvider {

  public function getName() {
    return 'checkstyle';
  }

  public function getTargets() {
    return array('checkstyle:checkstyle');
  }

  public function getAdditionalFlags() {
    return array();
  }

  public function getPathArgument(array $src_paths, array $lint_paths) {
    $relativePaths = array();
    foreach ($lint_paths as $path) {
      $relativePaths[] = $this->extractRelativeFilePath($src_paths, $path);
    }
    $path = implode(',', array_filter($relativePaths));
    return sprintf('-Dcheckstyle.includes="%s"', $path);
  }

  private function extractRelativeFilePath(array $src_paths, $path) {
    foreach ($src_paths as $prefix) {
      $idx = strpos($path, $prefix);
      if ($idx !== false) {

        $relative_path = substr($path, $idx + strlen($prefix));
        if (0 === strpos($relative_path, '/')) {
          $relative_path = substr($relative_path, 1);
        }

        return $relative_path;
      }
    }
  }

  public function parseLinterOutput(array $paths) {
    $parser = new CheckstyleParser();
    return $parser->parseAll('/target\/checkstyle-result\.xml$/i', $paths);
  }
}
