<?php

/**
 * Interface for lint providers based on top of Maven
*/
interface MavenLintProvider {

  /**
   * Retrieve the unique name to identify this lint.
   *
   * @return string
  */
  public function getName();

  /**
   * The targets to run in maven, such as pmd:pmd or checkstyle:checkstyle
   *
   * @return array
  */
  public function getMavenTargets();

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

  /**
   * Parse the generated output files.
   *
   * @param $paths array List of paths being linted.
   *
   * @return array List of ArcanistLintMessage
  */
  public function parseLinterOutput(array $paths);
}
