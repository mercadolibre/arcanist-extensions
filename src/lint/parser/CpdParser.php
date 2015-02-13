<?php

class CpdParser extends AbstractFileParser {
  protected function parse($file) {
    $messages = array();
    $report_dom = new DOMDocument();
    $content = file_get_contents($file);

    $ok = $report_dom->loadXML($content);
    if (!$ok) {
      throw new Exception('Arcanist could not load the linter output. '
        . 'Either the linter failed to produce a meaningful'
        . ' response or failed to write the file.');
    }

    $duplications = $report_dom->getElementsByTagName('duplication');

    foreach ($duplications as $duplicate) {
      $files = $duplicate->getElementsByTagName('file');
      $originalFile = $files->item(0);
      $duplicateFile = $files->item(1);
      $sourcePath = $originalFile->getAttribute('path');

      $description = sprintf('%d lines of code repetead at %s line %d',
        $duplicate->getAttribute('lines'),
        $duplicateFile->getAttribute('path'),
        $duplicateFile->getAttribute('line'));
      $sourceline = $originalFile->getAttribute('line');

      $prefix = 'E';

      $code = 'PMD.'.$prefix.'.CPD';

      $message = new ArcanistLintMessage();
      $message->setPath($sourcePath);
      $message->setCode($code);
      $message->setDescription($description);
      $message->setSeverity($this->getLintMessageSeverity($prefix));
      $message->setLine(intval($sourceline));

      $messages[] = $message;
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
