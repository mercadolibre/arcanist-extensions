<?php

/**
 * Interface for lint providers for meta linters.
*/
interface LintProvider {

  /**
   * Retrieve the unique name to identify this lint.
   *
   * @return string
  */
  public function getName();

  /**
   * The targets to run in the meta linter
   *
   * @return array
  */
  public function getTargets();

  /**
   * Parse the generated output files.
   *
   * @param $paths array List of paths being linted.
   *
   * @return array List of ArcanistLintMessage
  */
  public function parseLinterOutput(array $paths);
}
