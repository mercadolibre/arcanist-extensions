<?php

abstract class AbstractMetaLinter extends ArcanistSingleRunLinter {
  protected $_linters;
  private $_availableLints;

  public function __construct($baseProviderClass) {
    if (empty($baseProviderClass)) {
      throw new Exception(
        'Must provide a base provider class for meta linter.');
    }

    $rc = new ReflectionClass($baseProviderClass);
    if (!$rc->isSubclassOf('LintProvider')) {
      throw new Exception(
        'Base provider class must extend LintProvider.');
    }

    // Initialize all available lints
    $symbols = id(new PhutilSymbolLoader())
      ->setType('class')
      ->setConcreteOnly(true)
      ->setAncestorClass($baseProviderClass)
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

    $availableLintNames = array();
    foreach ($this->_availableLints as $lint) {
      $availableLintNames[] = '"'.$lint->getName().'"';
    }

    $options['lints'] = array(
      'type' => 'list<string>',
      'help' => 'The list of lint pugins to be run by '
        .$this->getLinterName()
        .' Currently supported values are: '
        .implode(', ', $availableLintNames),
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


  protected function parseLinterOutput($paths, $err, $stdout, $stderr) {
    $messages = array();

    if ($err) {
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
