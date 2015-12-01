<?php

class FindbugsParser extends AbstractFileParser {
  private $reverseFileIdx = array();
  private $srcdirsAreFolders = true;

  protected function parse($content) {
    $messages = array();
    $report_dom = new DOMDocument();

    $ok = $report_dom->loadXML($content);
    if (!$ok) {
      throw new Exception('Arcanist could not load the linter output. '
        .'Either the linter failed to produce a meaningful'
        .' response or failed to write the file.');
    }

    $bugs = $report_dom->getElementsByTagName('BugInstance');
    $srcdirs = $report_dom->getElementsByTagName('SrcDir');
    $srcdirs = $this->buildReverseIndex($srcdirs);

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
        $sourceline_list = $bug->getElementsByTagName('SourceLine');
        $sourceline = $sourceline_list->item($sourceline_list->length - 1);
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
      $source_path = $sourceline->getAttribute('sourcepath');
      $file_path = $this->getFile($source_path);

      $message = new ArcanistLintMessage();
      $message->setPath($file_path);
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

  private function allSrcdirsAreFolders(array &$srcdirs) {
    foreach ($srcdirs as $dir) {
      if (!is_dir($dir)) {
        return false;
      }
    }
    return true;
  }

  private function buildReverseIndex($srcdirs) {
    $dirs = array();
    foreach ($srcdirs as $dir) {
      $dirs[] = $dir->nodeValue;
    }
    if ($this->allSrcdirsAreFolders($dirs)) {
      $this->reverseFileIdx = $dirs;
      return;
    }
    $this->srcdirsAreFolders = false;

    foreach ($dirs as $dir) {
      $key = basename($dir);
      if (!isset($this->reverseFileIdx[$key])) {
        $this->reverseFileIdx[$key] = array();
      }
      $this->reverseFileIdx[$key][] = $dir;
    }
  }

  private function getFile($filename) {
    if ($this->srcdirsAreFolders) {
      foreach ($this->reverseFileIdx as $prefix) {
        $file = $prefix.'/'.$filename;
        if (file_exists($file)) {
          return $file;
        }
      }
      return null;
    }

    $files = $this->reverseFileIdx[basename($filename)];
    foreach ($files as $candidate) {
      $ends_with = substr_compare($candidate, $filename, -strlen($filename));
      if ($ends_with === 0) {
        return $candidate;
      }
    }
  }
}
