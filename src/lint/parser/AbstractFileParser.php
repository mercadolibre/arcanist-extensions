<?php

abstract class AbstractFileParser {

  protected abstract function parse($content);

  public final function parsePath($file, array $paths) {
    return $this->parseContent(file_get_contents($file), $paths);
  }

  public final function parseContent($content, array $paths) {
    $messages = $this->parse($content);

    /*
     * Filter paths to play nicely with arc lint --lintall
     * even if the linter couldn't narrow down the linting
    */
    foreach ($messages as $k => $msg) {
      $msg_path = $msg->getPath();

      $found = false;
      foreach ($paths as $path) {
        /*
         * We will check by doing endswith on both paths, but doing so needs a
         * haystack longer or equal than the needle.
        */
        if (strlen($msg_path) > strlen($path)) {
          $shortest = $path;
          $longest = $msg_path;
        } else {
          $shortest = $msg_path;
          $longest = $path;
        }

        // Poor man's endswith, not in sandard PHP library
        if (substr_compare($longest, $shortest, -strlen($shortest)) == 0) {
          $found = true;
          break;
        }
      }

      if (!$found) {
        // The path was not meant to be linted
        unset($messages[$k]);
      }
    }

    return $messages;
  }

  public final function parseAll($file_regex, array $paths) {
    $files = $this->findFilesByRegex($file_regex);

    $messages = array();
    foreach ($files as $file) {
      $messages = array_merge($messages, $this->parsePath($file, $paths));
    }

    return $messages;
  }

  private function findFilesByRegex($file_regex) {
    $directory = new RecursiveDirectoryIterator(getcwd());
    $iterator = new RecursiveIteratorIterator($directory);
    $regex = new RegexIterator($iterator, $file_regex,
      RecursiveRegexIterator::MATCH, RegexIterator::USE_KEY);
    $matches = iterator_to_array($regex);
    $files = array();
    foreach ($matches as $match) {
      $files[] = $match->getPathname();
    }
    if (!count($files)) {
      throw new Exception('Could not find any matching files.'
        . ' Check this project is correctly configured');
    }
    return $files;
  }
}
