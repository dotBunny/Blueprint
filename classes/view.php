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
        $folder = $this->project->OutputPath;/// . str_replace("/", DIRECTORY_SEPARATOR, $this->uri);
        if ( !is_dir($folder)) {
            mkdir($folder, $this->project->getDirectoryPermission(), true);
        }
        $buildPath = Core::BuildPath($folder, $this->output);

        file_put_contents($buildPath, $this->content);

        // Read back file into an array
        $fileArray = file($buildPath);

        foreach( $fileArray as $key => $value )
        {
            if( empty(trim($value)) ) unset($fileArray[$key]);
        }
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