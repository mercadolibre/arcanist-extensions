<?php

/**
 * Basic linter that uses regular expressions to find forbidden patterns.
 */
final class RegexpLinter extends ArcanistLinter {

  private $rules = array();

  public function getInfoName() {
    return pht('REGEXP');
  }

  public function getInfoDescription() {
    return pht('Detects simple violations through Regular Expressions.');
  }

  public function getLinterName() {
    return 'REGEXP';
  }

  public function getLinterConfigurationName() {
    return 'regexp';
  }

  protected function canCustomizeLintSeverities() {
    return false;
  }

  public function getLinterConfigurationOptions() {
    $options = array(
      'rules' => array(
        'type' => 'map<string, map<string, string>>',
        'help' => pht('Pass in custom rules by specifying the "regexp", '
            .'"message", and optionally, "fixup" and "severity".'),
      ),
    );

    return $options + parent::getLinterConfigurationOptions();
  }

  public function setLinterConfigurationValue($key, $value) {
    switch ($key) {
      case 'rules':
        // Validate required keys are present
        foreach ($value as $name => $rule) {
          $this->requireKey($rule, $name, 'regexp');
          $this->requireKey($rule, $name, 'message');
        }

        $this->rules = $value;
        return;
    }

    return parent::setLinterConfigurationValue($key, $value);
  }

  private function requireKey($rule, $ruleName, $key) {
    if (!isset($rule[$key])) {
      throw new Exception(
        pht('Rule %s is missing the "%s" to apply', $ruleName, $key));
    }
  }

  public function getLintSeverityMap() {
    return array_map(function ($rule) {
      return idx($rule, 'severity', ArcanistLintSeverity::SEVERITY_WARNING);
    }, $this->rules);
  }

  public function getLintNameMap() {
    return array_map(function () {
      return pht('Illegal pattern matched.');
    }, $this->rules);
  }

  public function lintPath($path) {
    foreach ($this->rules as $name => $rule) {
      $this->checkRule($path, $name, $rule);
    }
  }

  private function checkRule($path, $name, $rule) {
    $text = $this->getData($path);
    $matches = array();
    $num_matches = preg_match_all(
      $rule['regexp'],
      $text,
      $matches,
      PREG_OFFSET_CAPTURE);
    if (!$num_matches) {
      return;
    }
    foreach ($matches[0] as $match) {
      $original = $match[0];

      if (isset($rule['fixup'])) {
        $replacement = preg_replace($rule['regexp'], $rule['fixup'], $original);
        $message = pht(
          "%s. You wrote '%s', but did you mean '%s'?",
          $rule['message'],
          $original,
          $replacement);
      } else {
        $replacement = null;
        $message = pht(
          "%s. You wrote '%s', which is discouraged by rule '%s'",
          $rule['message'],
          $original,
          $name);
      }

      $this->raiseLintAtOffset(
        $match[1],
        $name,
        $message,
        $original,
        $replacement);
    }
  }

}
