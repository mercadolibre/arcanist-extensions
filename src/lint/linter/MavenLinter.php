<?php

class MavenLinter extends ArcanistSingleRunLinter {
  private $_srcPaths;
  private $_linters;
  private $_availableLints;

  public function __construct() {
    // Initialize all available lints
    $symbols = id(new PhutilSymbolLoader())
      ->setType('class')
      ->setConcreteOnly(true)
      ->setAncestorClass('MavenLintProvider')
      ->selectAndLoadSymbols();

    $this->_availableLints = array();
    foreach ($symbols as $symbol) {
      $this->_availableLints[] = newv($symbol['name'], array());
    }

    // Default paths for a java project
    $this->_srcPaths = array(
      'src/main/java',
      'src/test/java'
    );
  }

  public function getLinterName() {
    return 'maven';
  }

  public function getLinterConfigurationName() {
    return 'maven';
  }

  protected function canCustomizeLintSeverities() {
    return false;
  }

  public function getLinterConfigurationOptions() {
    $options = array();

    $options['paths'] = array(
      'type' => 'optional list<string>',
      'help' => 'An optional list of the build paths used by this project. '
        . 'This is needed only if you don\'t use a standard layout '
        . 'or are not using Java.'
    );

    $availableLintNames = array();
    foreach ($this->_availableLints as $lint) {
      $availableLintNames[] = '"' . $lint->getName() . '"';
    }

    $options['lints'] = array(
      'type' => 'list<string>',
      'help' => 'The list of lint pugins to be run by Maven.'
        . ' Currently supported values are: '
        . implode(', ', $availableLintNames)
    );

    return $options;
  }

  public function getInstallInstructions() {
    return pht('Have a pom.xml in the root configuring every plugin'
      . ' to be used for linting.');
  }

  public function getDefaultBinary() {
    $config = $this->getEngine()->getConfigurationManager();
    return $config->getConfigFromAnySource('bin.maven', 'mvn');
  }

  public function getVersion() {
    return false;
  }

  public function shouldExpectCommandErrors() {
    return true;
  }

  public function setLinterConfigurationValue($key, $value) {
    if ($key === 'paths') {
      $this->_srcPaths = $value;
    } else if ($key === 'lints') {
      foreach ($value as $linter_name) {
        $selected = null;

        foreach ($this->_availableLints as $linter) {
          if ($linter->getName() === $linter_name) {
            $selected = $linter;
            break;
          }
        }

        if ($selected === null) {
          throw new Exception(pht('Unrecognized maven linter: %s',
            $linter_name));
        }

        $this->_linters[] = $selected;
      }

      if (count($this->_linters) === 0) {
        throw new Exception(pht('Must specify at least one maven linter'));
      }
    }
  }

  public function getMandatoryFlags() {
    $flags = array();
    foreach ($this->_linters as $linter) {
      $flags = array_merge($flags, $linter->getMavenTargets());
    }
    $flags = array_unique($flags); // targets MAY be repeated

    foreach ($this->_linters as $linter) {
      $flags = array_merge($flags, $linter->getAdditionalFlags());
    }

    return $flags;
  }

  public function getDefaultFlags() {
    $config = $this->getEngine()->getConfigurationManager();
    return $config->getConfigFromAnySource(
      'lint.maven.options', array());
  }

  protected function getPathsArgumentForLinter($paths) {
    $args = array();
    foreach ($this->_linters as $linter) {
      $args[] = $linter->getPathArgument($this->_srcPaths, $paths);
    }

    return implode(' ', $args);
  }

  protected function parseLinterOutput($paths, $err, $stdout, $stderr) {
    if ($err) {
      $message = new ArcanistLintMessage();
      $message->setCode('MVN.COMPILE');
      $message->setDescription('Compilation failed.');
      $message->setSeverity(ArcanistLintSeverity::SEVERITY_ERROR);

      $messages[] = $message;
      return $messages;
    }

    $messages = array();
    foreach ($this->_linters as $linter) {
      $messages = array_merge($messages, $linter->parseLinterOutput($paths));
    }

    return $messages;
  }
}
