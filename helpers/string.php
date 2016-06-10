<?php

class StringHelper
{
    public static function startsWith($haystack, $needle)
    {
        // search backwards starting from haystack length characters from the end
        return $needle === "" || strrpos($haystack, $needle, -strlen($haystack)) !== false;
    }

    public static function endsWith($haystack, $needle)
    {
        // search forward starting from end minus needle length characters
        return $needle === "" || (($temp = strlen($haystack) - strlen($needle)) >= 0 && strpos($haystack, $needle, $temp) !== false);
    }
    public static function MaskCheck($item, $masks)
    {
        $maskCheck = false;
        foreach($masks as $mask)
        {
            if (\StringHelper::startsWith($item, $mask) || \StringHelper::endsWith($item, $mask))
            {
                $maskCheck = true;
            }
        }
        return $maskCheck;
    }
}