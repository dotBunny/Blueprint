<?php

class Tests extends Blueprint\Project {

    public function Initialize()
    {
        // Set the projects name internally
        $this->Name = "Tests Framework";

        // Turn off compression on JS/CSS files
        $this->setGlobalCompression(false);

        // Add some files / folders to ignore entirely
        $this->AddIgnore(".ignore");

        // Execute our base logic (finding all the templates and parsers)
        parent::Initialize();
    }
}