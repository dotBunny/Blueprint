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

    public function Process($owner = NULL)
    {
        Core::Output(MESSAGE, "Processing View \"" . $this->getName() . "\" (" . $this->getPath() . ")");

        if ($owner == NULL) {
            parent::Process($this);
        } else {
            parent::Process($owner);
        }

        $this->content = $this->project->ConvertPathing($this->content, 'src="', '"');
        $this->content = $this->project->ConvertPathing($this->content, 'href="', '"');
    }

    public function Update()
    {
           // Get the header so we know the parsers
        $this->header = Tag::FindNext(TAG_BLUEPRINT, $this->content, 0);


        if ($this->header != null && $this->header->IsValid())
        {
            // Set position to be at the end of the header tag
            $position = $this->header->getEndPosition();
        } else {
            // No header, start from beginning?
            $position = 0;
        }



        // Template In Templates
        while ($position < strlen($this->content)) {

			$start = Tag::FindNext(TAG_TEMPLATE_START, $this->content, $position);
			if ( $start == null || !$start->IsValid() ) {
    			$position = strlen($this->content);
                break;
			}

            // Check the end
            $end = Tag::FindNext(TAG_TEMPLATE_END, $this->content, $start->getEndPosition());
			// No more tags found
			if ( $end == null || !$end->IsValid() ) {
				$position = strlen($this->content);
				break;
			}



            if ( !empty($start->getPrimaryValue()))
            {

                Core::Output(INFO, "Found Template \"" . $start->getPrimaryValue() . "\" @ Position " . $start->getStartPosition() . "-" . $end->getEndPosition());

                $this->subtemplates[] = $start->getPrimaryValue();
				$template = clone $this->project->templates[$start->getPrimaryValue()];

                $newContent = $template->Process($this);

                // SOmething up here? Maybe it gets removed?
				$this->content =    substr($this->content, 0, $start->getEndPosition()) .
				                    $newContent .
				                    substr($this->content, $end->getStartPosition() - 1);

                // Modified length
				$position = $start->getEndPosition() + strlen($newContent) + $end->getLength();

			}
			else
			{
                $position = $start->getEndPosition();
			}
        }

        $this->content = $this->project->ConvertPathing($this->content, 'src="', '"', $this->getRelativePath());
        $this->content = $this->project->ConvertPathing($this->content, 'href="', '"', $this->getRelativePath());


        // Write View File Out w/ Updated Templates
        file_put_contents($this->path, $this->content);

          // Map Paths
    }

    public function getOutputPath()
    {
        if ( $this->getHeaders()->destination) {
            return $this->getHeaders()->destination;
        } else {
            return $this->output;
        }
    }





    public function Generate()
    {

        if ( $this->header->destination != null) {
            // Take relative add destination
            Core::Output(INFO, "Using Custom Destination " . $this->header->destination . " with " . $this->name);
        } else {
            Core::Output(INFO, "Using Default Destination Destination for " . $this->name);
        }

        $folder = $this->project->BuildPath;
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