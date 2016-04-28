<?php

namespace Blueprint;

class View extends Template
{
    protected $output;
    protected $uri;
    protected $name;

    public function __construct(&$project, $path) {

        parent::__construct($project, $path);

        // Unique Stuff
        $this->output =  end(explode(DIRECTORY_SEPARATOR, $path));
        $tempPath = str_replace($this->project->SitePath, "", $this->path);
/*
        $this->uri = substr($tempPath, 0, strlen($tempPath) - strlen($this->output));
        if ( substr($this->uri, 0, 1) != '/' )
        {
            $this->uri = "/" . $this->uri;
        }
*/
        $temp = explode(".", $this->output);
        $this->name = strtolower($temp[0]);
    }


    public function Generate()
    {

        Core::Output(INFO, "GENERATE!");
        print_r($this->header);



        if ( $this->header->destination != null) {
                 //  print("||" . $this->header->destination . "$$\n\r");

                    // Take relative add destination
            Core::Output(INFO, "Using Custom Destination " . $this->header->destination . " with " . $this->name);


        } else {
            Core::Output(INFO, "Using Default Destination Destination for " . $this->name);
        }


        //die();
        $folder = $this->project->OutputPath;/// . str_replace("/", DIRECTORY_SEPARATOR, $this->uri);
        if ( !is_dir($folder)) {
            mkdir($folder, $this->project->getDirectoryPermission(), true);
        }

         $buildPath = Core::BuildPath($folder, $this->output);
        if ( $this->header->destination != null )
        {
            $buildPath = Core::BuildPath($folder, $this->header->destination);
            if ( !is_dir(dirname($buildPath) )) {
                mkdir(dirname($buildPath), $this->project->getDirectoryPermission(), true);
            }
        }


        // Time to cleanup line endings (http://stackoverflow.com/questions/18376167/convert-ending-line-of-a-file-with-a-php-script)
        //Replace all the CRLF ending-lines by something uncommon
        $DontReplaceThisString = "\r\n";
        $specialString = "!£#!Dont_wanna_replace_that!#£!";

        $this->content = str_replace($DontReplaceThisString, $specialString, $this->content);

        //Convert the CR ending-lines into CRLF ones
        $this->content = str_replace("\r", "\r\n", $this->content);

        //Replace all the CRLF ending-lines by something uncommon
        $this->content = str_replace($DontReplaceThisString, $specialString, $this->content);

        //Convert the LF ending-lines into CRLF ones
        $this->content = str_replace("\n", "\r\n", $this->content);

        //Restore the CRLF ending-lines
        $this->content = str_replace($specialString, $DontReplaceThisString, $this->content);

        file_put_contents($buildPath, $this->content);

        // Read back file into an array
        $fileArray = file($buildPath);

        // Remove any empty lines
        foreach( $fileArray as $key => $value )
        {
            if( empty(trim($value)) ) unset($fileArray[$key]);
        }

        // Final write of file
        file_put_contents($buildPath, $fileArray);
    }

    public function getName()
    {
        return $this->name;
    }

    private function LineCheck($line)
    {
        if ( empty(trim($line)))
        {
            return false;
        }
    }


}