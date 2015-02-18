<?php

class FindbugsMavenLintProvider implements MavenLintProvider {

  public function getName() {
    return 'findbugs';
  }

  public function getTargets() {
    return array(
      'compile',
      'compiler:testCompile',
      'findbugs:findbugs'
    );
  }

  public function getAdditionalFlags() {
    return array('-Dfindbugs.includeTests=true');
  }

  public function getPathArgument(array $src_paths, array $lint_paths) {
    $classNames = array();
    foreach ($lint_paths as $path) {
      $relativePath = $this->extractRelativeFilePath($src_paths, $path);
      $class = str_replace(
        '/', '.', preg_replace('/\.java$/', '', $relativePath));
      $classNames[] = $class;
      /*
       * Let the hackish begin...
       *
       * We also want to analyze inner classes. Findbugs has no support
       * to do this directly, it can either get a specific class, or
       * a package. And we have no idea by the path of what inner
       * classes there may be... So we abuse the source!
       *  https://code.google.com/p/findbugs/source/browse/findbugs/src/java/edu/umd/cs/findbugs/ClassScreener.java
       * Since the incoming param is not strictly quoted,
       * we can use SOME RegExp expressions, and that's exactly what
       * we do here!
      */
      $classNames[] = $class . '\\$\\\\S+';
    }
    $classes = implode(',', $classNames);
    return sprintf('-Dfindbugs.onlyAnalyze="%s"', $classes);
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
    $parser = new FindbugsParser();
    return $parser->parseAll('/target\/findbugsXml\.xml$/i', $paths);
  }
}
