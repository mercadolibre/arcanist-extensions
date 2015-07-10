<?php

class PmdParser extends AbstractFileParser {
  protected function parse($content) {
    $messages = array();
    $report_dom = new DOMDocument();

    $ok = $report_dom->loadXML($content);
    if (!$ok) {
      throw new Exception('Arcanist could not load the linter output. '
        . 'Either the linter failed to produce a meaningful'
        . ' response or failed to write the file.');
    }

    $files = $report_dom->getElementsByTagName('file');

    $messages = array();
    foreach ($files as $file) {
      $violations = $file->getElementsByTagName('violation');
      $sourcePath = $file->getAttribute('name');

      foreach ($violations as $violation) {
        $description = $violation->textContent;
        $sourceline = $violation->getAttribute('beginline');

        $severity = $violation->getAttribute('priority');
        if ($severity < 3) {
          $prefix = 'E';
        } else {
          $prefix = 'W';
        }

        $code = 'PMD.'.$prefix.'.'.$violation->getAttribute('rule');

        $message = new ArcanistLintMessage();
        $message->setPath($sourcePath);
        $message->setCode($code);
        $message->setDescription($description);
        $message->setSeverity($this->getLintMessageSeverity($prefix));
        $message->setLine(intval($sourceline));

        $messages[] = $message;
      }
    }

    return $messages;
  }

  private function getLintMessageSeverity($code) {
    if ($code === 'W') {
      return ArcanistLintSeverity::SEVERITY_WARNING;
    } else {
      return ArcanistLintSeverity::SEVERITY_ERROR;
    }
  }
}
