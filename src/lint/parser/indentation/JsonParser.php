<?php

class JsonParser extends IndentationParser {

  public function getSupportedFileType() {
    return 'json';
  }

  public function consumeLine($text) {
    $tracked_state = array();
    $is_escaping = false;
    $delta_deepness = 0;

    foreach (str_split($text) as $char) {
      // If we are escaping, ignore whatever follows
      if ($is_escaping) {
        $is_escaping = false;
        continue;
      }

      // If it's a closure character, we check that we are not inside a string
      if ($this->insideLiteral && in_array($char, array('{', '}', '[', ']'))) {
        continue;
      }

      switch ($char) {
        case '{':
        case '[':
          $delta_deepness++;
          break;

        case '}':
        case ']':
          $delta_deepness--;
          break;

        case '\\':
          $is_escaping = true;
          break;

        case '"':
          $this->insideLiteral = !$this->insideLiteral;
          break;
      }
    }

    if ($delta_deepness > 0) {
      $this->deepnessLevel++;
    } else if ($delta_deepness < 0) {
      $this->deepnessLevel--;
    }
  }
}
