<?php

abstract class AbstractMetaLinter extends ArcanistSingleRunLinter {
  protected $_linters;
  private $_availableLints;

  public function __construct($base_provider_class) {
    if (empty($base_provider_class)) {
      throw new Exception(
        'Must provide a base provider class for meta linter.');
    }

    $rc = new ReflectionClass($base_provider_class);
    if (!$rc->isSubclassOf('LintProvider')) {
      throw new Exception(
        'Base provider class must extend LintProvider.');
    }

    // Initialize all available lints
    $symbols = id(new PhutilSymbolLoader())
      ->setType('class')
      ->setConcreteOnly(true)
      ->setAncestorClass($base_provider_class)
      ->selectAndLoadSymbols();

    $this->_availableLints = array();
    foreach ($symbols as $symbol) {
      $this->_availableLints[] = newv($symbol['name'], array());
    }
  }

  protected function canCustomizeLintSeverities() {
    return false;
  }

  public function getLinterConfigurationOptions() {
    $options = array();

    $available_lint_names = array();
    foreach ($this->_availableLints as $lint) {
      $available_lint_names[] = '"'.$lint->getName().'"';
    }

    $options['lints'] = array(
      'type' => 'list<string>',
      'help' => 'The list of lint pugins to be run by '
        .$this->getLinterName()
        .'. Currently supported values are: '
        .implode(', ', $available_lint_names).'.',
    );

    return $options;
  }

  public function getVersion() {
    return false;
  }

  public function shouldExpectCommandErrors() {
    return true;
  }

  public function setLinterConfigurationValue($key, $value) {
    if ($key === 'lints') {
      foreach ($value as $linter_name) {
        $selected = null;

        foreach ($this->_availableLints as $linter) {
          if ($linter->getName() === $linter_name) {
            $selected = $linter;
            break;
          }
        }

        if ($selected === null) {
          throw new Exception(pht('Unrecognized %s linter: %s',
            $this->getLinterName(),
            $linter_name));
        }

        $this->_linters[] = $selected;
      }

      if (count($this->_linters) === 0) {
        throw new Exception(pht('Must specify at least one %s linter',
          $this->getLinterName()));
      }
    }
  }

  public function getDefaultFlags() {
    $config = $this->getEngine()->getConfigurationManager();
    return $config->getConfigFromAnySource(
      'lint.'.$this->getLinterConfigurationName().'.options', array());
  }

  public function getMandatoryFlags() {
    $flags = array();
    foreach ($this->_linters as $linter) {
      $flags = array_merge($flags, $linter->getTargets());
    }
    return array_unique($flags); // targets MAY be repeated
  }

  public function shouldLintBinaryFiles() {
    foreach ($this->_linters as $linter) {
      if ($linter->shouldLintBinaryFiles()) {
        return true;
      }
    }

    return false;
  }

  public function shouldLintDirectories() {
    foreach ($this->_linters as $linter) {
      if ($linter->shouldLintDirectories()) {
        return true;
      }
    }

    return false;
  }

  /**
   * Checks if the linter output indicates a compile error and reports shouldn't be parsed.
   *
   * @param status The status code with which the linter exited.
   * @param stdout The linter's output to stdout.
   * @param stderr The linter's output to stderr.
   *
   * @return bool True if it is a compilation error, false if it's not and reports should be parsed.
  */
  protected function isCompileError($status, $stdout, $stderr) {
    return $status != 0;
  }

  protected function parseLinterOutput($paths, $err, $stdout, $stderr) {
    $messages = array();

    if ($this->isCompileError($err, $stdout, $stderr)) {
      $message = new ArcanistLintMessage();
      $message->setCode('COMPILE');
      $message->setDescription(
        "Compilation failed.\nstdout: $stdout\nstderr: $stderr");
      $message->setSeverity(ArcanistLintSeverity::SEVERITY_ERROR);
      $message->setName('Compile error');

      $messages[] = $message;
      return $messages;
    }

    foreach ($this->_linters as $linter) {
      $messages = array_merge($messages, $linter->parseLinterOutput($paths));
    }

    return $messages;
  }
}
