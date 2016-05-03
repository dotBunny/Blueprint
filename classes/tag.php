<?php

namespace Blueprint;

define(__NAMESPACE__ ."\TAG_END", "-->");
define(__NAMESPACE__ ."\TAG_START", "<!-- ");

define(__NAMESPACE__ ."\TAG_BLUEPRINT", "BLUEPRINT");
define(__NAMESPACE__ ."\TAG_TEMPLATE_START", "START");
define(__NAMESPACE__ ."\TAG_TEMPLATE_END", "END");

class Tag
{
    private $baseContent;
    private $content;
    private $exploded;

    private $keyword;
    private $values = array();
    private $keyValues = array ();


    private $startPosition;
    private $endPosition;

	private $valid;

	public function __construct($contents)
	{
    	// Clear out annoying spaced items in lists
    	$contents = str_replace(", ", ",", $contents);

    	$this->baseContent = $contents;

        $exploded = explode(" ", $contents);
        $this->keyword = strtoupper($exploded[0]);
        unset($exploded[0]);

        $this->values = $exploded;


        foreach($this->values as $key => $value) {
            $split = explode("=", $value);


            if ( strpos($split[1],',') > 0 ) {

                $newValues = explode(",", str_replace("\"", "", $split[1]));
                for($i = 0; $i < count($newValues); $i++)
                {
                    $newValues[$i] = trim($newValues[$i]);
                }
                $this->keyValues[trim($split[0])] = $newValues;

            } else {
                $this->keyValues[trim($split[0])] = trim(str_replace("\"", "", $split[1]));
            }


        }

        switch($this->keyword) {
            case TAG_BLUEPRINT:
            case TAG_TEMPLATE_START:
            case TAG_TEMPLATE_END:
                $this->valid = true;
                break;
            default:
                $this->valid = false;
                break;
        }

        return $this;
	}

    public function __get($name)
    {
        return $this->keyValues[trim($name)];;
    }

    public function __set($key, $value)
    {
        $this->keyValues[trim($key)] = trim(value);
    }

	public static function FindNext($tag, $content, $offset = 0)
	{
        $startPosition = strpos($content, TAG_START . $tag, $offset);
        if ( $startPosition === false  ) {
            return null;
        }

        $endPosition = strpos($content, TAG_END, $startPosition);

        $length = ($endPosition - ($startPosition + strlen(TAG_START)));

        // Create our new tag
        $tag = new Tag(trim(substr($content, $startPosition + strlen(TAG_START), $length)));

        // Set reference positions
        $tag->setPositions($startPosition, $endPosition + strlen(TAG_END));

        return $tag;
	}


	public static function Remove($tag, $content) {
    	return substr($content, 0, $tag->getStartPosition()) . substr($content, $tag->getEndPosition());;
	}

	public function IsValid()
	{
		return $this->valid;
	}

    public function getEndPosition()
	{
    	return $this->endPosition;
	}
	public function getKeyValues()
	{
    	return $this->keyValues;
	}

	public function getKeyword()
	{
    	return $this->keyword;
	}
	public function getPrimaryValue()
	{
    	return array_values($this->values)[0];
    }
	public function getStartPosition()
	{
    	return $this->startPosition;
	}
	public function getValues()
	{
    	return $this->values;
	}

	public function setPositions($start, $end)
	{
        $this->startPosition = $start;
        $this->endPosition = $end;
	}
}