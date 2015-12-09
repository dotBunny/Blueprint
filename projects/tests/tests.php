<?php

class Tests extends Blueprint\Project {

    protected $ignoreCompression = array(
        "*.min.*",
        "content/test",
        "content/test2/test.js"
    );


    public function Initialize()
    {

        // Set the projects name internally
        $this->SetName("Tests Framework");

        // Set the output folder for the project, be it an absolute path, or a path relative to the default output folder
        $this->SetOutputPath("tests");

        // Turn off compression on JS/CSS files
        $this->SetGlobalCompression(false);

        // Add some files / folders to ignore entirely
        $this->AddIgnore(".DS_Store");
        $this->AddIgnore(".git");
        $this->AddIgnore(".svn");

        parent::Initialize();
    }

    public function Generate()
    {
        // Call base function
        parent::Generate();
    }


}