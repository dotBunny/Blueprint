<?php

namespace Blueprint;

class Template {

    private $project;

    protected $path;
    protected $name;
    protected $parsers;


    protected $baseContent;
    protected $content;
    protected $info;



    public function __construct($project, $path) {
        $this->project = $project;
        $this->path = $path;
        if ( file_exists($this->path) ) {
            $this->baseContent = file_get_contents($this->path);
            $this->content = $this->baseContent;
        }

        // Read and process first line (template definition)
        $tag = $this->project->blueprint->FindNextTag("BLUEPRINT", $this->content, 0);
        $header = $this->project->blueprint->GetTagInfo($tag);
        if ( $this->project->GetRemoveTags()) {
            $this->content = trim(str_replace($tag, "", $this->content));
        }

        $this->name = $header["name"];
        $this->parsers = explode(",", $header["parsers"]);
    }

    public function Process()
    {
        foreach($this->project->GetParsers() as $names => $parser)
        {

            $this->content = $parser->Process($this->content);
        }
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