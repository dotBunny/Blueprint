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
            $this->parsers = $this->header->getValues();
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

	        foreach($this->parsers as $name) {
		       Core::Output(INFO, "Processing " . $this->path . " with " . $name);
		       $this->content = $this->project->GetParser($name)->Process($this->content);
	        }
        }

        return $this->content;
    }

    public function getContent()
    {
        return $this->content;
    }



        /**
         * Sets a value for replacing a specific tag.
         *
         * @param string $key the name of the tag to replace
         * @param string $value the value to replace
         */
/*
        public function Set($key, $value) {
            $this->values[$key] = $value;
        }
*/

        /**
         * Outputs the content of the template, replacing the keys for its respective values.
         *
         * @return string
         */
/*
        public function Output() {
        	/**
        	 * Tries to verify if the file exists.
        	 * If it doesn't return with an error message.
        	 * Anything else loads the file contents and loops through the array replacing every key for its value.
        	 */
           /* if (!file_exists($this->file)) {
            	return "Error loading template file ($this->file).<br />";
            }
            $output = file_get_contents($this->file);

            foreach ($this->values as $key => $value) {
            	$tagToReplace = "[@$key]";
            	$output = str_replace($tagToReplace, $value, $output);
            }

            return $output;
        }
*/

        /**
         * Merges the content from an array of templates and separates it with $separator.
         *
         * @param array $templates an array of Template objects to merge
         * @param string $separator the string that is used between each Template object
         * @return string
         */
       // static public function Merge($templates, $separator = "\n") {
        	/**
        	 * Loops through the array concatenating the outputs from each template, separating with $separator.
        	 * If a type different from Template is found we provide an error message.
        	 */
         /*   $output = "";

            foreach ($templates as $template) {
            	$content = (get_class($template) !== "Template")
            		? "Error, incorrect type - expected Template."
            		: $template->output();
            	$output .= $content . $separator;
            }

            return $output;
        }*/
    }