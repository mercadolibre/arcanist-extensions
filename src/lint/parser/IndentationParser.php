<?php

abstract class IndentationParser {
  protected $deepnessLevel = 0;
  protected $insideLiteral = false;

  /**
   * Resets the parser's internal state. MUST be callled before starting to parse
   * a new file.
   */
  public function reset() {
    $this->deepnessLevel = 0;
    $this->insideLiteral = false;
  }

  /**
   * Retrieves the expected indentation level for the next line.
   *
   * @return int
  */
  public function getExpectedIndentationLevel() {
    return $this->deepnessLevel;
  }

  /**
   * Returns true if the line is within a multiline literal and should be ignored.
   * You must still pass it to `consumeLine`.
   *
   * @return boolean
  */
  public function isInsideLiteral() {
    return $this->insideLiteral;
  }

  /**
   * Parses the given line, updating internal status as to the expected
   * indentation level for the next one.
   *
   * @param text The contents of the line to be analyzed.
   */
  abstract public function consumeLine($text);

  /**
   * Gets the name of the supported file type. For example, 'XML' or 'JSON'.
   *
   * @return string
   */
  abstract public function getSupportedFileType();
}
