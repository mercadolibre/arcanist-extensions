<?php

final class ArcanistCheckstyleLinter extends ArcanistExternalLinter {

  private $_srcPaths;
  private $_aggregate = true;


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
        'help' => 'An optional flag to indicate wether the report should run in aggregate mode or not. '
            . 'This value should typically be false for single-module projects. True by default.'
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
    $target = $this->_aggregate ? 'checkstyle:checkstyle-aggregate' : 'checkstyle:checkstyle';
    return array(
      $target,
      '-Dcheckstyle.resourceIncludes=""'
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

  protected function buildOutputPath($path) {
    return sys_get_temp_dir() . '/' . str_replace('/', '-', $this->extractRelativeFilePath($path));
  }

  protected function getPathArgumentForLinterFuture($path) {
    $file = $this->extractRelativeFilePath($path);
    $temp_file = $this->buildOutputPath($path);
    return csprintf('-Dcheckstyle.includes=%s -Dcheckstyle.output.file=%s', $file, $temp_file);
  }

  protected function parseLinterOutput($path, $err, $stdout, $stderr) {

    $report_dom = new DOMDocument();
    $tmp_file = $this->buildOutputPath($path);
    $content = file_get_contents($tmp_file);
    unlink($tmp_file);

    $ok = $report_dom->loadXML($content);
    if (!$ok) {
      return false;
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

