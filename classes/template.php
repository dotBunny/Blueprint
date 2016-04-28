<?php

namespace Blueprint;

class Template {

    protected $project;
    protected $path;
    protected $parsers;
    protected $baseContent;
    protected $content;
    protected $header;


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
            //TOOD CHANGE THIS TO MAKING IT GET the parse array entry

            $this->parsers = $this->header->parsers;
        }
    }


    public function Process()
    {
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

		// Template In Template
        while ($position < strlen($this->content)) {
	        // Search through for all templates and and replace contents with parent template, ignoring anything in there that is a template
			// There should be no nested templates at this stage
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

            if ( !empty($start->getPrimaryValue())){

    			if($this->project->getRemoveTags())
    			{
    				$this->content =
    					substr($this->content, 0, $start->getStartPosition()) .
    					$this->project->templates[$start->getPrimaryValue()]->Process() .
    					substr($this->content, $end->getEndPosition());

    					$position = $end->getEndPosition();
    			} else {

    				$template = $this->project->templates[$start->getPrimaryValue()]->GetTemplate();

    				$this->content =
    					substr($this->content, 0, $start->getEndPosition()) .
    					$this->project->templates[$start->getPrimaryValue()]->Process() .
    					substr($this->content, $end->getStartPosition() - 1);

    				$position = $end->getEndPosition() + strlen($template);

    			}
			} else {
                $position = $start->getEndPosition();
			}
		}

        // Run Parsers
        if ( !is_null($this->parsers) && count($this->parsers) > 0 ) {

            if (is_array($this->parsers)) {
    	        foreach($this->parsers as $name) {
    		       Core::Output(INFO, "Processing " . $this->path . " with " . $name);
    		       $this->content = $this->project->GetParser($name)->Process($this->content);
    	        }
	        } else {
    	         Core::Output(INFO, "Processing " . $this->path . " with " . $this->parsers);
                 $this->content = $this->project->GetParser($this->parsers)->Process($this->content);
	        }
        }

        // System Replacer
        $this->content = $this->project->GetParser("SystemReplacer")->Process($this->content);

        return $this->content;
    }

    public function getContent()
    {
        return $this->content;
    }
}