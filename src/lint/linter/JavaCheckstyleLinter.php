<?php

final class ArcanistCheckstyleLinter extends ArcanistSingleRunLinter {

  private $_srcPaths;
  private $_aggregate = true;
  private $outputPath = '';


  public function getLinterName() {
    return 'checkstyle';
  }

  public function getLinterConfigurationName() {
    return 'checkstyle';
  }

  public function getLinterConfigurationOptions() {
    $options = parent::getLinterConfigurationOptions();

    $options['paths'] = array(
        'type' => 'optional list<string>',
        'help' => 'An optional list of the build paths used by this project. '
            . 'This is needed only if you don\'t use a standard layout.'
    );

    $options['aggregate'] = array(
        'type' => 'optional bool',
        'help' => 'An optional flag to indicate wether the report should run'
            . ' in aggregate mode or not. This value should typically be false'
            . ' for single-module projects. True by default.'
    );

    return $options;
  }

  public function setLinterConfigurationValue($key, $value) {
    if ($key == "paths") {
      $this->_srcPaths = $value;
    } else if ($key == "aggregate") {
      $this->_aggregate = (bool) $value;
    }
  }

  public function getMandatoryFlags() {
    $target = $this->_aggregate ?
        'checkstyle:checkstyle-aggregate' : 'checkstyle:checkstyle';
    $temp_file = $this->buildOutputPath();
    return array(
      $target,
      '-Dcheckstyle.resourceIncludes=""',
      "-Dcheckstyle.output.file=$temp_file"
    );
  }

  public function getInstallInstructions() {
    return pht('Just have a mvn plugin with checkstyle plugin');
  }

  public function getDefaultFlags() {
    $config = $this->getEngine()->getConfigurationManager();
    return $config->getConfigFromAnySource('lint.checkstyle.options', array());
  }

  public function getDefaultBinary() {
    $config = $this->getEngine()->getConfigurationManager();
    return $config->getConfigFromAnySource('lint.checkstyle.bin', 'mvn');
  }

  public function getVersion() {
    return false;
  }

  public function shouldExpectCommandErrors() {
    return true;
  }

  protected function extractRelativeFilePath($path) {

    if (!$this->_srcPaths) {
      $this->_srcPaths = array(
        'src/main/java',
        'src/test/java'
      );
    }

    foreach ($this->_srcPaths as $prefix) {
      $idx = strpos($path, $prefix);
      if ($idx !== false) {

        $relative_path = substr($path, $idx + strlen($prefix));
        if (0 === strpos($relative_path, '/')) {
          $relative_path = substr($relative_path, 1);
        }

        return $relative_path;
      }
    }
  }

  protected function buildOutputPath() {
    if (!$this->outputPath) {
        $this->outputPath = tempnam(sys_get_temp_dir(), 'checkstyle-');
    }
    return $this->outputPath;
  }

  protected function getPathsArgumentForLinter($paths) {
      $absolutePaths = array();
      foreach ($paths as $path) {
          $absolutePaths[] = $this->extractRelativeFilePath($path);
      }
      $path = implode(',', $absolutePaths);
      return sprintf('-Dcheckstyle.includes="%s"', $path);
  }

  protected function parseLinterOutput($paths, $err, $stdout, $stderr) {

    $report_dom = new DOMDocument();
    $tmp_file = $this->buildOutputPath();
    $content = file_get_contents($tmp_file);
    unlink($tmp_file);

    if (!$content) {
        throw new ArcanistUsageException('Maven failed to lint this project.'
            . ' Reason:' . PHP_EOL . $stdout);
    }

    $ok = $report_dom->loadXML($content);
    if (!$ok) {
        throw new ArcanistUsageException('Arcanist could not load the linter'
            . ' output. Either the linter failed to produce a meaningful'
            . ' response or failed to write the file.');
    }

    $files = $report_dom->getElementsByTagName('file');
    $messages = array();
    foreach ($files as $file) {
      foreach ($file->childNodes as $child) {
        if (!($child instanceof DOMElement)) {
          continue;
        }

        $severity = $child->getAttribute('severity');
        if ($severity == 'error') {
          $prefix = 'E';
        } else {
          $prefix = 'W';
        }

        $code = 'CS.'.$prefix.'.'.$child->getAttribute('source');
        $path = $file->getAttribute('name');

        $message = new ArcanistLintMessage();
        $message->setPath($path);
        $message->setLine($child->getAttribute('line'));
        $message->setChar($child->getAttribute('column'));
        $message->setCode($code);
        $message->setDescription($child->getAttribute('message'));
        $message->setSeverity($this->getLintMessageSeverity($code));

        $messages[] = $message;
      }
    }

    return $messages;
  }

  protected function getDefaultMessageSeverity($code) {
    if (preg_match('/^CS\\.W\\./', $code)) {
      return ArcanistLintSeverity::SEVERITY_WARNING;
    } else {
      return ArcanistLintSeverity::SEVERITY_ERROR;
    }
  }

}
