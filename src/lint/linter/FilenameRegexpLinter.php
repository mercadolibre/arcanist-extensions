<?php

/**
 * Basic linter that uses regular expressions to find forbidden patterns.
 */
final class FilenameRegexpLinter extends ArcanistLinter {

  const LINT_INVALID_NAME = 1;

  private $regexp = null;

  public function getInfoName() {
    return pht('FilenameRegexpLinter');
  }

  public function getInfoDescription() {
    return pht('Detects simple filename violations through Regular Expressions.');
  }

  public function getLinterName() {
    return 'FILENAMEREGEXP';
  }

  public function getLinterConfigurationName() {
    return 'filenameregexp';
  }

  public function getLinterConfigurationOptions() {
    $options = array(
      'regexp' => array(
        'type' => 'string',
        'help' => pht('Pass in the regexp to validate filenames.'),
      ),
    );

    return $options + parent::getLinterConfigurationOptions();
  }


  public function getLintNameMap() {
    return array(
      self::LINT_INVALID_NAME => pht('Invalid name'),
    );
  }

  public function setLinterConfigurationValue($key, $value) {
    switch ($key) {
        case 'regexp':
            $this->regexp = $value;
            return;
    }

    return parent::setLinterConfigurationValue($key, $value);
  }

  public function lintPath($path) {
    $filename = basename($path);
    $match = preg_match($this->regexp, $filename);

    if (!$match) {
        $message = pht(
            "The file '%s' doesn't match with %s'",
            $path,
            $this->regexp
        );

        $this->raiseLintAtPath(
            self::LINT_INVALID_NAME,
            $message
        );
    }
  }

}
