<?php

class FindbugsParser extends AbstractFileParser {
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

    $directory = new RecursiveDirectoryIterator(getcwd());
    $iterator = new RecursiveIteratorIterator($directory);

    $bugs = $report_dom->getElementsByTagName('BugInstance');

    $messages = array();
    foreach ($bugs as $bug) {
      $description = $bug->getElementsByTagName('LongMessage');
      $description = $description->item(0);
      $description = $description->nodeValue;
      $sourcelineList = $bug->getElementsByTagName('SourceLine');
      $sourceline = $sourcelineList->item($sourcelineList->length - 1);

      $severity = $bug->getAttribute('priority');
      if ($severity >= 5) {
        $prefix = 'E';
      } else {
        $prefix = 'W';
      }

      $code = 'FB.'.$prefix.'.'.$bug->getAttribute('type');

      // File can be in any of the analyzed folders...
      $sourcePath = $sourceline->getAttribute('sourcepath');
      $fileRegex = '/^.+'.preg_quote($sourcePath, '/').'$/';
      $regex = new RegexIterator($iterator, $fileRegex,
        RecursiveRegexIterator::GET_MATCH);
      foreach ($regex as $match) {
        $filePath = $match[0];
      }

      $message = new ArcanistLintMessage();
      $message->setPath($filePath);
      $message->setCode($code);
      $message->setDescription($description);
      $message->setSeverity($this->getLintMessageSeverity($code));

      // do we have a start line?
      $line = $sourceline->getAttribute('start');
      if ($line != '') {
        $message->setLine(intval($line));
      }

      $messages[] = $message;
    }

    return $messages;
  }

  private function getLintMessageSeverity($code) {
    if (substr($code, 5) === 'FB.W.') {
      return ArcanistLintSeverity::SEVERITY_WARNING;
    } else {
      return ArcanistLintSeverity::SEVERITY_ERROR;
    }
  }
}
