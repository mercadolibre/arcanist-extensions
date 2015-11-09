<?php

class CheckstyleParser extends AbstractFileParser {
  private $name = 'Checkstyle';
  private $callsign = 'CS';

  public function setName($name, $callsign) {
    $this->name = $name;
    $this->callsign = $callsign;
  }

  protected function parse($content) {
    $messages = array();
    $report_dom = new DOMDocument();

    $ok = $report_dom->loadXML($content);
    if (!$ok) {
      throw new Exception('Arcanist could not load the linter output. '
        .'Either the linter failed to produce a meaningful'
        .' response or failed to write the file.');
    }

    $files = $report_dom->getElementsByTagName('file');
    foreach ($files as $file) {
      foreach ($file->childNodes as $child) {
        if (!($child instanceof DOMElement)) {
          continue;
        }

        $severity = $child->getAttribute('severity');
        if ($severity === 'error') {
          $prefix = 'E';
        } else {
          $prefix = 'W';
        }

        // This is a java fully-qualified class name
        $rule_complete_name = $child->getAttribute('source');
        $rule = substr(strrchr($rule_complete_name, '.'), 1);
        if (!$rule) {
            $rule = 'UNKNOWN';
        }
        $code = $this->callsign.'.'.$prefix.'.'.$rule;

        $path = $file->getAttribute('name');

        $message = new ArcanistLintMessage();
        $message->setPath($path);
        $message->setLine(intval($child->getAttribute('line')));
        $message->setChar(intval($child->getAttribute('column')));
        $message->setCode($code);
        $message->setDescription($child->getAttribute('message'));
        $message->setSeverity($this->getLintMessageSeverity($code));
        $message->setName($this->name);

        $messages[] = $message;
      }
    }

    return $messages;
  }

  private function getLintMessageSeverity($code) {
    $prefix = preg_quote($this->callsign, '/');
    if (preg_match('/^'.$prefix.'\\.W\\./', $code)) {
      return ArcanistLintSeverity::SEVERITY_WARNING;
    } else {
      return ArcanistLintSeverity::SEVERITY_ERROR;
    }
  }
}
