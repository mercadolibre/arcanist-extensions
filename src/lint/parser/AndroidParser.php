<?php

class AndroidParser extends AbstractFileParser {
  protected function parse($file) {
    $messages = array();
    $content = file_get_contents($file);
    $filexml = simplexml_load_string($content);

    if ($filexml === false) {
      throw new Exception('Arcanist could not load the linter output. '
        . 'Either the linter failed to produce a meaningful'
        . ' response or failed to write the file.');
    }

    if ($filexml->attributes()->format < 4) {
      throw new ArcanistUsageException('Unsupported Android lint'
        . ' output version. Please update your Android SDK to the'
        . ' latest version.');
    } else if ($filexml->attributes()->format > 4) {
      throw new ArcanistUsageException('Unsupported Android lint'
        . ' output version. Arc Lint needs an update to match.');
    }

    foreach ($filexml as $issue) {
      $loc_attrs = $issue->location->attributes();
      $issue_attrs = $issue->attributes();

      $message = new ArcanistLintMessage();
      $message->setPath((string) $loc_attrs->file);
      // Line number and column are irrelevant for
      // artwork and other assets
      if (isset($loc_attrs->line)) {
        $message->setLine(intval($loc_attrs->line));
      }
      if (isset($loc_attrs->column)) {
        $message->setChar(intval($loc_attrs->column));
      }
      $message->setName((string) $issue_attrs->id);
      $message->setCode('AND.' . (string) $issue_attrs->category);
      $message->setDescription(preg_replace('/^\[.*?\]\s*/', '',
        $issue_attrs->message));

      // Setting Severity
      if ($issue_attrs->severity->__toString() === 'Error'
          || $issue_attrs->severity->__toString() === 'Fatal') {
        $message->setSeverity(ArcanistLintSeverity::SEVERITY_ERROR);
      } else if ($issue_attrs->severity->__toString() === 'Warning') {
        $message->setSeverity(ArcanistLintSeverity::SEVERITY_WARNING);
      } else {
        $message->setSeverity(ArcanistLintSeverity::SEVERITY_ADVICE);
      }

      $messages[] = $message;
    }

    return $messages;
  }
}
