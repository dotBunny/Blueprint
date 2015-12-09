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




    public function __construct(&$project, $path) {
        $this->project = $project;
        $this->path = $path;

        if ( !file_exists($this->path) ) {
            return;
        }

        $this->baseContent = file_get_contents($this->path);
        $this->content = $this->baseContent;

        // Read and process first line (view definition)
        $header = $this->project->blueprint->FindNextTag(\Blueprint\TAG_BLUEPRINT, $this->content, 0, $this->project->GetRemoveTags());

        // Grab information

        // Handle File
        $this->output =  end(explode(DIRECTORY_SEPARATOR, $path));
        $tempPath = str_replace($this->project->GetSitePath(), "", $this->path);
        $this->uri = substr($tempPath, 0, strlen($tempPath) - strlen($this->output));
        if ( !empty($header["info"]["uri"])) {
            $this->uri = $header["info"]["uri"];
        }

        if ( substr($this->uri, 0, 1) != '/' )
        {
            $this->uri = "/" . $this->uri;
        }

        if ( !empty($header["info"]["output"])) {
            $this->output = $header["info"]["output"];
        }


        $temp = explode(".", $this->output);
        $this->name = strtolower($temp[0]);

        if ( !empty($header["info"]["name"])) {
            $this->name = $header["info"]["name"];
        }



        $this->parsers = explode(",", $header["info"]["parsers"]);
    }

    public function Process()
    {
        // Get Templates In Place - They will process if they have processors on them

        $position = 0;

        while ($position < strlen($this->content)) {
	        // Search through for all templates and and replace contents with parent template, ignoring anything in there that is a template
			// There should be no nested templates at this stage
			$template_start = $this->project->blueprint->FindNextTag(\Blueprint\TAG_TEMPLATE_START, $this->content, $position, false);

			$template_end = $this->project->blueprint->FindNextTag(\Blueprint\TAG_TEMPLATE_END, $this->content, $template_start["openingEndIndex"], false);

			// No more tags found
			if ( $template_end["info"]["valid"] == 0 ) {
				$position = strlen($this->content);
				break;
			}

			if($this->project->GetRemoveTags())
			{
				$this->content =

					substr($this->content, 0, $template_start["openingStartIndex"]) .
					$this->project->templates[$template_start["info"]["name"]]->GetTemplate() .
					substr($this->content, $template_end["openingEndIndex"] + strlen(\Blueprint\TAG_END));

					$position = $template_end["openingEndIndex"];
			} else {
    			print "NOT REMOVING";
				$template = $this->project->templates[$template_start["info"]["name"]]->GetTemplate();

				$this->content =
					substr($this->content, 0, $template_start["openingEndIndex"] + strlen(\Blueprint\TAG_END)) .
					$template .
					substr($this->content, $template_end["openingStartIndex"] - 1);

				$position = $template_end["openingEndIndex"] + strlen(\Blueprint\TAG_END) + strlen($template);

			}

			// Next loop


		}

		// Find the end tag






        // Find template start / end


        // check if valid template
        // replace

        // Parse View

/*

        if ( !is_null($this->parsers) && count($this->parsers) > 0 ) {
	        foreach($this->parsers as $name) {
		        $this->content = $this->project->GetParsers()[$name]->Process($this->content);
	        }
        }
*/
    }

    public function Generate()
    {

        $folder = $this->project->GetOutputPath() . str_replace("/", DIRECTORY_SEPARATOR, $this->uri);
        if ( !is_dir($folder)) {
            mkdir($folder, $this->project->GetDirectoryPermission(), true);
        }
        $buildPath =  $folder . $this->output;
        file_put_contents($buildPath, $this->content);

        // Read back file into an array
        $fileArray = file($buildPath);

        foreach( $fileArray as $key => $value )
        {
            if( empty(trim($value)) ) unset($fileArray[$key]);
        }
        file_put_contents($buildPath, $fileArray);
    }

    function LineCheck($line)
    {
        if ( empty(trim($line)))
        {
            return false;
        }
    }
}