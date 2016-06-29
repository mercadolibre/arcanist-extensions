<?php

/**
 * Linter for checking indentation in any file.
 */
class IndentationLinter extends ArcanistLinter {
  const INDENT_TYPE_SPACES = 'spaces';
  const INDENT_TYPE_TABS = 'tabs';

  private $parsersAvailable = array();
  private $supportedFileTypes = array();

  private $parser;
  private $indentationType;
  private $indentationStr;
  private $indentRegex;

  public function __construct() {
    // Initialize all the available parsers
    $symbols = id(new PhutilSymbolLoader())
      ->setType('class')
      ->setConcreteOnly(true)
      ->setAncestorClass('IndentationParser')
      ->selectAndLoadSymbols();

    foreach ($symbols as $symbol) {
      $parser = newv($symbol['name'], array());
      $this->parsersAvailable[] = $parser;
      $this->supportedFileTypes[] = $parser->getSupportedFileType();
    }
  }

  public function getInfoName() {
    return pht('Indentation');
  }

  public function getInfoDescription() {
    return pht(
      'Checks consistency in the indentation inside files. '.
      'No checks are performed as for the validity of the content, only indentation of each. '.
      'The linter will assume it is valid to inline everything, only checking new lines. '.
      'The file extensions that are being currently supported are: %s.',
      implode(', ', $this->supportedFileTypes));
  }

  public function getLinterName() {
    return 'indentation';
  }

  public function getLinterConfigurationName() {
    return 'indentation';
  }

  public function getLintNameMap() {
    return array(
      'E.BAD_INDENTATION' => pht('wrong-indentation-set'),
      'E.MIXED' => pht('error-mixed-indentation'),
    );
  }

  protected function getDefaultMessageSeverity($code) {
    if ($code[0] === 'W') {
      return ArcanistLintSeverity::SEVERITY_WARNING;
    } else {
      return ArcanistLintSeverity::SEVERITY_ERROR;
    }
  }

  /**
   * Outputs the configuration for the linter
   */
  public function getLinterConfigurationOptions() {
    $options = array(
      'indent_with' => array(
        'type' => 'string',
        'help' => pht(
          "Sets the indentation format.\n".
          'It can be one of the followings: `%s`, `#-%s` '.
          '(where `#` stands for a number, e.g. `2-%s`).',
          self::INDENT_TYPE_TABS, self::INDENT_TYPE_SPACES,
          self::INDENT_TYPE_SPACES),
      ),
      'file_type' => array(
        'type' => 'string',
        'help' => pht(
          'How to interpret the contents of the file. Currently supported types are:'.
          implode(', ', $this->supportedFileTypes).'.'),
      ),
    );

    return $options + parent::getLinterConfigurationOptions();
  }

  /**
   * Grabs the configuration set in .arclint
   */
  public function setLinterConfigurationValue($key, $value) {
    switch ($key) {
      case 'file_type':
        foreach ($this->parsersAvailable as $parser) {
          if ($parser->getSupportedFileType() === $value) {
            $this->parser = $parser;
            return;
          }
        }

        throw new Exception(pht('Unrecognized %s parser: %s',
          $this->getLinterName(),
          $value));
        break;

      case 'indent_with':
        $matches = array();

        if ($value === self::INDENT_TYPE_TABS) {
          $this->indentationType = self::INDENT_TYPE_TABS;
          $this->indentationStr = "\t";
          $this->indentRegex = '/^(\t*)/';

        } else if (preg_match(sprintf('/^(\d+)\-%s$/', self::INDENT_TYPE_SPACES), $value, $matches)) {
          $this->indentationType = self::INDENT_TYPE_SPACES;
          $amount = $matches[1];
          $this->indentationStr = str_repeat(' ', $amount);
          $this->indentRegex = '/^( *)/';

        } else {
          throw new Exception(pht(
            " > '%s' is not a valid configuration.\n > Try doing ".
            '`arc linters %s --verbose` to learn more about how to use this linter.',
            $value,
            $this->getLinterName()));
        }
        break;

      default:
        parent::setLinterConfigurationValue($key, $value);
        break;
    }
  }


  /**
   * Hook called for each path to be linted
   */
  public function lintPath($path) {
    $text = $this->getData($path);

    $this->checkIndentation($text);
  }

  /**
   * Parses the file by using one of the classes defined in `parser/` (and extended
   * from Indentation Parser).
   */
  private function checkIndentation($text) {
    $split = explode("\n", $text);
    $matches = array();

    $this->parser->reset();
    foreach ($split as $line_number => $text) {
      if (empty($text)) {
        continue;
      }

      // Get indenting substring at line
      preg_match($this->indentRegex, $text, $matches);
      $actual_indentation = $matches[1];

      $expected = $this->parser->getExpectedIndentationLevel();

      // Let the parser prepare for the next line
      $this->parser->consumeLine($text);

      // Is the line indented with the appropriate character only?
      $first_char = substr($text, strlen($actual_indentation), 1);
      if ($first_char === ' ' || $first_char == "\t") {
        $this->raiseLintAtLine($line_number + 1, null, 'E.MIXED',
          'Mixed tabs and spaces used for indentation');
      } else {
        // If the next line is closing stuff, it will be in lower levels...
        $actually_expected = min($expected, $this->parser->getExpectedIndentationLevel());

        if ($actual_indentation !== str_repeat($this->indentationStr, $actually_expected)) {
          $this->raiseLintAtLine($line_number + 1, null, 'E.BAD_INDENTATION',
            pht('Bad indentation, expected %s nesting levels', $actually_expected));
        }
      }
    }
  }
}
