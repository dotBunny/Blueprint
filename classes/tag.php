<?php

namespace Blueprint;

define(__NAMESPACE__ ."\TAG_END", "-->");
define(__NAMESPACE__ ."\TAG_START", "<!-- ");
define(__NAMESPACE__ ."\TAG_REPLACE_SPACE", "[[[SPACE]]]");
define(__NAMESPACE__ ."\TAG_REPLACE_SPACE_LENGTH", strlen(TAG_REPLACE_SPACE));

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


        // Before we explode based on a space, we need to find spaces inside of quotes and replace them
        $quoteCount = substr_count($contents, '"');
        if ( ($quoteCount % 2) == 0)
        {


            $openQuote = false;
            $openQuoteIndex = 0;
            $lastQuoteIndex = 0;
            $foundQuote = true;
            while($foundQuote)
            {
                $positionOfQuote = strpos($contents, '"', $lastQuoteIndex);

                if ($positionOfQuote !== false) {

                    $foundQuote = true;


                   if ( !$openQuote )
                   {
                       $openQuoteIndex = $positionOfQuote;
                       $openQuote = true;
                       $lastQuoteIndex = $positionOfQuote + 1;
                   }
                   else
                   {
                       // closing quote
                       $openQuote = false;


                       // Replace String Based On Index
                       $spaceCount = substr_count(substr($contents, $openQuoteIndex, $positionOfQuote - $openQuoteIndex), ' ');


                       $contents =  substr($contents, 0, $openQuoteIndex) .
                                    str_replace(" ", TAG_REPLACE_SPACE ,substr($contents, $openQuoteIndex, $positionOfQuote - $openQuoteIndex)) .
                                    substr($contents, $positionOfQuote);

                        $lastQuoteIndex = $positionOfQuote + (($spaceCount * TAG_REPLACE_SPACE_LENGTH) - $spaceCount) + 1;

                   }



                }
                else
                {
                    $foundQuote = false;
                }
            }
        }
        else
        {
            Core::Output(ERROR, "Uneven number of quotes in tag: " . $contents);
        }

        $exploded = explode(" ", $contents);

        // Put the spaces back into the mix
        for($i = 0; $i < count($exploded); $i++)
        {
            $exploded[$i] = str_replace(TAG_REPLACE_SPACE, " ",  $exploded[$i]);
        }


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
	public function getLength()
	{
    	return strlen($this->baseContent);
	}

	public function setPositions($start, $end)
	{
        $this->startPosition = $start;
        $this->endPosition = $end;
	}
}