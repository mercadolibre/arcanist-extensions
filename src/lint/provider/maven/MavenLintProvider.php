<?php

/**
 * Interface for lint providers based on top of Maven
*/
interface MavenLintProvider extends LintProvider {

  /**
   * Rerieve an array of any additional flags to pass to Maven
   *
   * @return array
  */
  public function getAdditionalFlags();

  /**
   * Argument to narrow the linting to the given paths.
   *
   * Optional, parsers MUST filter themselves,
   * but providing it may speed up linting.
   *
   * @param $src_paths array The template for source sets.
   * @param $lint_paths array The paths to lint.
   *
   * @return string
  */
  public function getPathArgument(array $src_paths, array $lint_paths);
}
