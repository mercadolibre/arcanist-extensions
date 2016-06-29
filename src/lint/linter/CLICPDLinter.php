<?php

class CLICPDLinter extends ArcanistSingleRunLinter {

  private $minimunTokens = 100;
  private $exclude = array();
  private $language = null;
  private $defaultPaths = array();

  public function getLinterName() {
    return 'cpd-cli';
  }

  public function getLinterConfigurationName() {
    return 'cpd-cli';
  }

  public function getLinterConfigurationOptions() {
    $options = array(
      'minimum-tokens' => array(
        'type' => 'optional int',
        'help' => pht('Minimum number of tokens to match'),
      ),
      'exclude' => array(
        'type' => 'optional list<string>',
        'help' => pht('Paths to exclude'),
      ),
      'language' => array(
        'type' => 'string',
        'help' => pht('Language to check'),
      ),
      'paths' => array(
        'type' => 'optional list<string>',
        'help' => pht('Paths to always include when running'),
      ),
    );

    return $options + parent::getLinterConfigurationOptions();
  }

  public function setLinterConfigurationValue($key, $value) {
    switch ($key) {
    case 'minimum-tokens':
      $this->minimunTokens = $value;
      return;
    case 'exclude':
      $this->exclude = $value;
      return;
    case 'language':
      $this->language = $value;
      return;
    case 'paths':
      $this->defaultPaths = $value;
      return;
    }

    return parent::setLinterConfigurationValue($key, $value);
  }

  protected function getPathsArgumentForLinter($paths) {
    return implode(' ', array_map(function ($path) {
      return '--files "'.$path.'"';
    }, array_merge($this->defaultPaths, $paths)));
  }

  protected function getDefaultBinary() {
    $config = $this->getEngine()->getConfigurationManager();
    return $config->getConfigFromAnySource('bin.cpd', 'pmd');
  }

  protected function getMandatoryFlags() {
    return array(
      'cpd',
      '--language '.$this->language,
      '--minimum-tokens '.$this->minimunTokens,
      '--format xml',
      '--skip-duplicate-files',
    ) + array_map(function ($path) {
      return '--exclude "'.$path.'"';
    }, $this->exclude);

  }

  protected function getDefaultFlags() {
    return array();
  }

  public function getVersion() {
      return '';
  }

  protected function parseLinterOutput($paths, $err, $stdout, $stderr) {

    // In this mode, CPD doesn't output anything when no matches were found
    if ($stdout == '') {
      return array();
    }

    $parser = new CpdParser();
    return $parser->parseContent($stdout, $paths);
  }

}
