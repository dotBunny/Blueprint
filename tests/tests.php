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
        $this->AddIgnore(".DS_Store");


        $this->Replace("TITLE", "Blueprint Test Generated @ " . date("Y-m-d H:i:s"));
    }
}