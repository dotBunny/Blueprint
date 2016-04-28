<?php

namespace Blueprint;

class Replace extends Parser {

    private $keyValues = array();
    public $startTag = "{";
    public $endTag = "}";

    public function Process($content)
    {
        foreach ($this->keyValues as $key => $value) {

            	$content = str_replace($this->startTag . $key . $this->endTag, $value, $content);
        }
        return $content;
    }

    public function Set($key, $value) {
        $this->keyValues[$key] = $value;
    }

    public function Get($key)
    {
        return $this->keyValues[$key];
    }

    public function SetStart($opener)
    {
        $this->startTag = $opener;
    }
    public function SetEnd($closer)
    {
        $this->endTag = $closer;
    }
}