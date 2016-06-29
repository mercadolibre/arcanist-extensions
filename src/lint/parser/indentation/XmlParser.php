<?php

class XmlParser extends IndentationParser {
  private $literalCdata = false;
  private $insideComment = false;
  private $insideOpeningTag = false;

  public function getSupportedFileType() {
    return 'xml';
  }

  public function reset() {
    parent::reset();
    $this->literalCdata = false;
    $this->insideComment = false;
    $this->insideOpeningTag = false;
  }

  public function consumeLine($text) {
    $delta_deepness = 0;

    foreach (str_split($text) as $col => $char) {
      // If we are in a literal, ignore interesting chars
      if ($this->insideLiteral && in_array($char, array('<'))) {
        continue;
      }

      switch ($char) {
        case '<':
          // is it a <![CDATA[ ?
          if (substr($text, $col, 9) === '<![CDATA[') {
            $this->literalCdata = true;
            $this->insideLiteral = true;
          } else if (substr($text, $col, 4) === '<!--') { // is it a comment?
            $this->insideComment = true;
            $delta_deepness++;
          } else if (substr($text, $col, 2) === '</') { // is it a closing tag?
            $this->insideOpeningTag = false;
            $delta_deepness--;
          } else {
            // it's just an opening tag...
            $this->insideOpeningTag = true;
            $delta_deepness++;
          }
          break;

        case '>':
          if ($this->insideLiteral) {
            // is it a <![CDATA[..]]> ?
            if ($this->literalCdata && $col >= 2 && substr($text, $col - 2, 3) === ']]>') {
              $this->literalCdata = false;
              $this->insideLiteral = false;
            }
          } else if ($this->insideComment) {
            // is it a --> close?
            if ($col >= 2 && substr($text, $col - 2, 3) === '-->') {
              $delta_deepness--;
              $this->insideComment = false;
            }
          } else {
            $this->insideOpeningTag = false;

            // is it a /> close?
            if ($col > 0 && (substr($text, $col - 1, 2) === '/>'
                || substr($text, $col - 1, 2) === '?>')) {
              $delta_deepness--;
            }
          }
          break;

        case '"':
        case "'":
          /*
           * According to the spec: https://www.w3.org/TR/xml/#NT-AttValue
           * no > can be unescaped on an attribute, so this is safe.
           * Remeber we don't validate the XML.
          */

          // Is this an attribute?
          if ($this->insideOpeningTag) {
            $this->insideLiteral = !$this->insideLiteral;
          }
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
