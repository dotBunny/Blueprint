<?php

namespace Blueprint;

class Template {

    protected $project;
    protected $path;
    protected $parsers;
    protected $baseContent;
    protected $content;
    protected $header;
    protected $owner = NULL;
    protected $name;

    protected $subtemplates = array();

    public function __construct(&$project, $path) {
        $this->project = $project;
        $this->path = $path;
        if ( !file_exists($this->path) ) { return; }

        $this->baseContent = file_get_contents($this->path);
        $this->content = trim($this->baseContent);

        // Read and process first line (template definition)

        $this->header = Tag::FindNext(TAG_BLUEPRINT, $this->content, 0);

        if ($this->header != null && $this->header->IsValid())
        {

            $this->parsers = $this->header->parsers;
        }

        $temp = explode(".", end(explode(DIRECTORY_SEPARATOR, $this->path)));
        $this->name = strtolower($temp[0]);
    }

    public function getName()
    {
        return $this->name;
    }

    public function getPath()
    {
        return $this->path;
    }

	public function getHeaders()
	{
		return $this->header;
	}

	public function getOwner()
	{
    	if ( $this->owner != NULL ) {
        	return $this->owner;
    	}
        else {
            return $this;
        }
	}

	// TODO : This function should be used in the header.php parser, just it is infinite ;/
	public function getAbsoluteOwner()
	{
    	// Recursive all the way up to the top
    	if ( $this->owner != NULL && $this->owner != $this) {
        	return $this->owner->getAbsoluteOwner();
    	}
        else {
            return $this;
        }
	}

	public function getContent()
	{
		return $this->content;
	}

    public function Process($owner = NULL)
    {

        if ( $owner != NULL ) {
            $this->owner = $owner;
        }

        // Get a fresh copy of the template
	    $this->content = $this->baseContent;

	    // We have to find/remove the header tag
        $this->header = Tag::FindNext(TAG_BLUEPRINT, $this->content, 0);


        if ($this->header != null && $this->header->IsValid())
        {
            // Set position to be at the end of the header tag
            $position = $this->header->getEndPosition;

            // If we are removing the header tag were going to need to update that position
            if ( $this->project->getRemoveTags() ) {
                    $this->content = Tag::Remove($this->header, $this->content);
                    $position = 0;
            }
        } else {

            // No header, start from beginning?
            $position = 0;
        }

		// Template In Templates
        while ($position < strlen($this->content)) {
	        // Search through for all templates and and replace contents with parent template, ignoring anything in there that is a template
			// There should be no nested templates at this stage
			//print "\n\r". $this->content . "\n\r";


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


/*
            if (!array_key_exists($start->getPrimaryValue(), $this->project->templates)) {
                $position = $start->getEndPosition();
                continue;
            }
*/

            if ( !empty($start->getPrimaryValue()))
            {

                Core::Output(INFO, "Found Template \"" . $start->getPrimaryValue() . "\" @ Position " . $start->getStartPosition() . "-" . $end->getEndPosition());
                //" [" . substr($this->content, $start->getStartPosition(), $start->getEndPosition() - $start->getStartPosition()) . "] to Position " . $end->getEndPosition() .
                //" [" . substr($this->content, $end->getStartPosition(), $end->getEndPosition() - $end->getStartPosition()) . "]");


                $this->subtemplates[] = $start->getPrimaryValue();


				$template = clone $this->project->templates[$start->getPrimaryValue()];
                $newContent = $template->Process($this);

                // SOmething up here? Maybe it gets removed?
    			if($this->project->getRemoveTags())
    			{
    				$this->content =    substr($this->content, 0, $start->getStartPosition()) .
    				                    $newContent .
    				                    substr($this->content, $end->getEndPosition());

                    // Get the start position, add the length of the new content, and then we should be at the right
                    // place to start searching forward
    				$position = $start->getStartPosition() + strlen($newContent);

    			}
    			else
    			{
    				$this->content =    substr($this->content, 0, $start->getEndPosition()) .
    				                    $newContent .
    				                    substr($this->content, $end->getStartPosition() - 1);

                    // Modified length
    				$position = $start->getEndPosition() + strlen($newContent) + $end->getLength();
    			}
			}
			else
			{

    			Core::Output(WARNING, "\n\r" . substr($this->content, $start->getStartPosition(), $start->getEndPosition() - $start->getStartPosition()) . "\n\r");
                $position = $start->getEndPosition();
			}
		}

        // Run Parsers
        if ( !is_null($this->parsers) && count($this->parsers) > 0 ) {

            if (is_array($this->parsers)) {
    	        foreach($this->parsers as $name) {
    		       Core::Output(INFO, "Processing " . $this->path . " with " . $name);
                   $this->content = $this->project->GetParser($name)->Process($this);
    	        }
	        } else {
    	         Core::Output(INFO, "Processing " . $this->path . " with " . $this->parsers);
                 $this->content = $this->project->GetParser($this->parsers)->Process($this);
	        }
        }

        // System Replacer
        $this->content = $this->project->GetParser("SystemReplacer")->Process($this);


        return $this->content;
    }
}