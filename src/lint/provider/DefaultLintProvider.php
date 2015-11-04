<?php

/**
 * A default implementation of LintProvider that only lints text-based files.
*/
abstract class DefaultLintProvider implements LintProvider {

  public function shouldLintBinaryFiles() {
    return false;
  }

  public function shouldLintDirectories() {
    return false;
  }
}
