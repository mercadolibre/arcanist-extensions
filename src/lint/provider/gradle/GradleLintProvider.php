<?php

/**
 * Interface for lint providers based on top of Gradle
*/
interface GradleLintProvider extends LintProvider {
  /**
   * Checks if the given error message corresponds to lint errors being detected
   *
   * @param error_message The error message to be analyzed.
   *
   * @return boolean
  */
  public function isLintDetectedMessage($error_message);
}
