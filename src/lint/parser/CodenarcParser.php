<?php

class CodenarcParser extends AbstractFileParser {

  protected function parse($content) {
    $messages = array();
    $report_dom = new DOMDocument();

    $ok = $report_dom->loadXML($content);
    if (!$ok) {
      throw new Exception('Arcanist could not load the linter output. '
        .'Either the linter failed to produce a meaningful'
        .' response or failed to write the file.');
    }


    $directory = new RecursiveDirectoryIterator(getcwd(),
      FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS);
    $iterator = new RecursiveIteratorIterator($directory);

    $rules_dom = $report_dom->getElementsByTagName('Rule');
    $rules = array();
    foreach ($rules_dom as $rule_dom) {
      $rule_description = $rule_dom->getElementsByTagName('Description')->item(0)->textContent;
      $rules[strtoupper($rule_dom->getAttribute('name'))] = $rule_description;
    }

    $files = $report_dom->getElementsByTagName('File');

    $messages = array();
    foreach ($files as $file) {
      $path = $file->parentNode->getAttribute('path');
      $violations = $file->getElementsByTagName('Violation');
      $source_path = $file->getAttribute('name');
      $file_regex = '/^.+'.preg_quote($source_path, '/').'$/';
      $regex = new RegexIterator($iterator, $file_regex, RecursiveRegexIterator::GET_MATCH);
      foreach ($regex as $match) {
        $file_path = $match[0];
        break;
      }

      foreach ($violations as $violation) {

        $sourceline = $violation->getAttribute('lineNumber');

        $severity = $violation->getAttribute('priority');
        if ($severity < 3) {
          $prefix = 'E';
        } else {
          $prefix = 'W';
        }

        $rule = $violation->getAttribute('ruleName');
        $description = $rules[strtoupper($rule)];

        $name = $rule;
        $code = 'Codenarc.'.$prefix.'.'.$rule;

        $message = new ArcanistLintMessage();
        $message->setPath($file_path);
        $message->setCode($code);
        $message->setDescription($description);
        $message->setSeverity($this->getLintMessageSeverity($prefix));
        $message->setLine(intval($sourceline));
        $message->setName($name);

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
