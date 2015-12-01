<?php

class FindbugsParser extends AbstractFileParser {
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
        FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO
        | FilesystemIterator::SKIP_DOTS);
    $iterator = new RecursiveIteratorIterator($directory);

    $bugs = $report_dom->getElementsByTagName('BugInstance');

    $messages = array();
    foreach ($bugs as $bug) {
      $description = $bug->getElementsByTagName('LongMessage');
      $description = $description->item(0);
      $description = $description->nodeValue;

      if (!$bug->hasChildNodes()) {
        return;
      }

      $children = $bug->childNodes;
      $sourceline = null;
      foreach ($children as $child) {
        if ($child->nodeName == 'SourceLine') {
          $sourceline = $child;
          break;
        }
      }

      // if we haven't found a sourceline in the buginstance's children
      // we use the last sourceline in the report.
      if ($sourceline === null) {
        $sourcelineList = $bug->getElementsByTagName('SourceLine');
        $sourceline = $sourcelineList->item($sourcelineList->length - 1);
      }

      $severity = $bug->getAttribute('priority');
      if ($severity >= 5) {
        $prefix = 'E';
      } else {
        $prefix = 'W';
      }

      $code = 'FB.'.$prefix.'.'.$bug->getAttribute('category');

      // Go from KIND_OF_ERROR to Kind of error
      $type = $bug->getAttribute('type');
      $name = ucfirst(strtolower(str_replace('_', ' ', $type)));

      // File can be in any of the analyzed folders...
      $sourcePath = $sourceline->getAttribute('sourcepath');
      $fileRegex = '/^.+'.preg_quote($sourcePath, '/').'$/';
      $regex = new RegexIterator($iterator, $fileRegex,
        RecursiveRegexIterator::GET_MATCH);
      foreach ($regex as $match) {
        $filePath = $match[0];
        break;
      }

      $message = new ArcanistLintMessage();
      $message->setPath($filePath);
      $message->setCode($code);
      $message->setDescription($description);
      $message->setSeverity($this->getLintMessageSeverity($code));
      $message->setName($name);

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
