<?php

/**
 * Linter for checking indentation in any file.
 */
final class IndentationLinter extends ArcanistLinter {

  public $regexIndent = '/^(?:( )+|\t+)/';
  public $regexTabsOrSpaces = '/^(?:[ \t]*)/';

  private $offset = 0;

  public function getInfoName() {
    return pht('Indentation');
  }

  public function getInfoDescription() {
    return pht('Checks consistency in the indentation inside the files.');
  }

  public function getLinterName() {
    return 'indentation';
  }

  public function getLinterConfigurationName() {
    return 'indentation';
  }

  public function getLintNameMap() {
    return array(
      'W' => pht('wrong-indentation-set'),
    );
  }

  protected function getDefaultMessageSeverity($code) {
    if ($code === 'W') {
      return ArcanistLintSeverity::SEVERITY_WARNING;
    } else {
      return ArcanistLintSeverity::SEVERITY_ERROR;
    }
  }

  /**
   * Initialize/Reset each variable for a new inspection
   */
  public function initVariables() {
    $this->offset = 0;
  }

  /**
   * Hook called for each path to be linted
   */
  public function lintPath($path) {
    $text = $this->getData($path);

    $this->initVariables();
    $this->reportWrongIndentation($text);
  }

  /**
   * Searches for the indentation with higher amount of occurrences, or with the
   * bigger weight.
   */
  private function getMostUsed($indents) {

    $max = array(
      'occurrences' => 0,
      'weight' => 0,
      'indentation' => 0,
    );

    foreach ($indents as $key => $indentation) {

      $occurrences = $indentation['occurrences'];
      $weight = $indentation['weight'];

      // If it has the same amount of occurrences, we choose the one with
      // greater weight
      if ($occurrences > $max['occurrences'] ||
          $occurrences === $max['occurrences'] && $weight > $max['weight']) {

          $max['occurrences'] = $occurrences;
          $max['weight'] = $weight;
          $max['indentation'] = $key;
        }
    }

    return $max['indentation'];

  }

  /**
   * Guess the indentation used and returns an array with the size ($amount) of
   * the used indentation, which type is it (tabs, spaces), and an example block
   * ($indent, e.g. '    ' - for 4 spaces indentation).
   */
  private function guessIndentation($indents, $tabs, $spaces) {

    $amount = $this->getMostUsed($indents);

    $type = null;
    $indent = '';

    if ($amount) {
      if ($tabs > $spaces) {
        $type = 'tabs';
        $indent = str_repeat("\t", $amount);

      } else {
        $type = 'spaces';
        $indent = str_repeat(' ', $amount);
      }
    }

    return array(
      'amount' => $amount,
      'type' => $type,
      'indent' => $indent,
    );

  }

  /**
   * We are trying to guess what's the current identation of the file by
   * analising its spaces and tabs.
   *
   * Based in the "detect-indent" of Sindre Sorhus
   * (https://github.com/sindresorhus/detect-indent)
   *
   * See also:
   * https://medium.com/firefox-developer-tools/detecting-code-indentation-eff3ed0fb56b#.r1qh0v3sv
   */
  private function analyseIndentation($text) {

    // Remember how many indents/unindents has occurred for a given size
    // and how much lines follow a given indentation
    //
    // indents = {
    //    3: [1, 0],
    //    4: [1, 5],
    //    5: [1, 0],
    //   12: [1, 0],
    // }
    $indents = array();

    // We kept track of whether the tabs or spaces are the most used
    $tabs = 0;
    $spaces = 0;

    // Size of the previous line (for comparison)
    $prev = 0;

    // Current identation (for weight purposes)
    $current_diff = false;

    foreach (explode("\n", $text) as $line) {

      // Remove trailing spaces and tabs from the end of the line
      $line = rtrim($line);

      // Ignore empty lines (mark as -1)
      if (strlen($line) === 0) {
        continue;
      }

      $indent_length = 0;
      $matches = [];
      preg_match($this->regexIndent, $line, $matches);

      if (!empty($matches)) {
        $indent_length = strlen($matches[0]);

        // If it uses tabs, we don't have a group captured
        if (count($matches) > 1) {
          $spaces++;
        } else {
          $tabs++;
        }
      }

      $diff = abs($indent_length - $prev);
      $prev = $indent_length;

      // We have detected a difference in the value of the indentation
      // we store such difference. So, if a line is indented by 8 spaces, and
      // another one with 10, we add another vote to 2 spaces indentation.
      if ($diff) {

        if (array_key_exists($diff, $indents)) {
          $indents[$diff]['occurrences'] += 1;

        } else {
          $indents[$diff] = array(
            'occurrences' => 1,
            'weight' => 0,
          );
        }

        $current_diff = $diff;

      // If there isn't a difference, we add more weight to the previous indent
      } else if ($current_diff) {
        $indents[$current_diff]['weight'] += 1;
      }
    }

    return $this->guessIndentation($indents, $tabs, $spaces);
  }

  /**
   * Warn about wrong indentation type
   */
  private function warnAboutWrongIndentationType($detected_indentation, $guessed_indentation) {

    if ($guessed_indentation['type'] !== $detected_indentation) {

      $description = pht(
        'Indentation uses %s, but %s were detected',
        $guessed_indentation['type'],
        $detected_indentation);

      return $description;
    }

    return false;

  }

  /**
   * First rule: we warn about mixed indentation.
   */
  private function warnAboutMixedIndentation($splitted_text, $guessed_indentation) {

    $warn_message = false;
    $what_to_look_for = false;

    // If we're having tabs, we need to check for spaces
    if ($guessed_indentation['type'] === 'tabs') {
      $warn_message = $this->warnAboutWrongIndentationType('spaces', $guessed_indentation);
      $what_to_look_for = '/ /';

    // Else, if there are spaces, we need to look up for tabs
    } else if ($guessed_indentation['type'] === 'spaces') {
      $warn_message = $this->warnAboutWrongIndentationType('tabs', $guessed_indentation);
      $what_to_look_for = '/\t/';

    } else {
      throw new Exception(pht('Unsupported indentation type: [%s].', $guessed_indentation['type']));
    }

    foreach ($splitted_text as $line) {

      $this->offset++;

      // Ignore empty lines
      if (strlen($line) === 0) {
        continue;
      }

      $matches = [];
      preg_match($this->regexTabsOrSpaces, $line, $matches);

      // We ignore the line if there aren't matches
      if (empty($matches) || strlen($matches[0]) === 0) {
        continue;
      }

      // Now we proceed with the actual checking
      $indent_length = strlen($matches[0]);

      // We make the search for the mixed indentation
      if (preg_match($what_to_look_for, $matches[0]) === 1) {
        $this->raiseLintAtLine(
          $this->offset,
          1,
          'W',
          $warn_message);

        continue;
      }

    }

  }

  /*
   * Goes through the text, and warns about those lines with wrong indentation
   * (according to our guess about which indentation the file has).
   */
  public function reportWrongIndentation($text) {

    // First, get what indentation we have
    $guessed_indentation = $this->analyseIndentation($text);

    $splitted_text = preg_split('/\n/', $text);

    $this->warnAboutMixedIndentation($splitted_text, $guessed_indentation);

  }

}
