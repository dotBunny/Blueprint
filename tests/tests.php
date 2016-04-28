<?php

// php blueprint.php generate projects/tests
class Tests extends Blueprint\Project {

    public function Initialize()
    {
        // Set the projects name internally
        $this->Name = "Tests Framework";

        // Turn off compression on JS/CSS files
        $this->setGlobalCompression(false);

        // Add some files / folders to ignore entirely
        $this->AddIgnore(".ignore");

        $this->Replace("TITLE", "Blueprint Test Generated @ " + date("Y-m-d H:i:s"));
    }

    public function Replace($key, $value)
    {
        if (is_null($this->parsers["replace"]))
        {
            $this->parsers["replace"] = new Blueprint\Replace($this);
        }
        $this->parsers["replace"]->Set($key, $value);
    }
}