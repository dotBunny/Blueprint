<?php

namespace Blueprint;

class View
{
    private $project;

    protected $path;
    protected $name;
    protected $uri;
    protected $output;
    protected $parsers;


    protected $baseContent;
    protected $content;




    public function __construct($project, $path) {
        $this->project = $project;
        $this->path = $path;
        if ( file_exists($this->path) ) {

            $this->baseContent = file_get_contents($this->path);
            $this->content = $this->baseContent;
        }

        // Read and process first line (view definition)
        $tag = $this->project->blueprint->FindNextTag("BLUEPRINT", $this->content, 0);
        $header = $this->project->blueprint->GetTagInfo($tag);

        if ( $this->project->GetRemoveTags()) {
            $this->content = trim(str_replace($tag, "", $this->content));
        }

        // Grab information
        $this->name = $header["name"];
        $this->uri = $header["uri"];

        if ( substr($this->uri, 0, 1) != '/' )
        {
            $this->uri = "/" . $this->uri;
        }

        $this->output = $header["output"];
        $this->parsers = explode(",", $header["parsers"]);
    }

    public function Process()
    {
        foreach($this->project->GetParsers() as $names => $parser)
        {
            $this->content = $parser->Process($this->content);
        }
    }

    public function Generate()
    {

        $folder = $this->project->GetOutputPath() . str_replace("/", DIRECTORY_SEPARATOR, $this->uri);
        if ( !is_dir($folder)) {
            mkdir($folder, $this->project->GetDirectoryPermission(), true);
        }
        $buildPath =  $folder . $this->output;
        file_put_contents($buildPath, $this->content);
    }
}