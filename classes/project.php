<?php

namespace Blueprint;

abstract class Project
{
    private $name;

    private $outputPath;
    private $viewPath;
    private $parserPath;
    private $templatePath;

    private $workingDirectory;
    private $compression = true;
    private $copyResources = true;
    private $removeTags = true;

    private $directoryPermission = 0777;
    private $filePermission = 0666;

    private $ignoreFiles = array();


    protected $views = array();
    protected $templates = array();
    protected $parsers = array();

    public $blueprint;

    protected $ignoreCompression = array(
        "*.min.*"
    );

    protected $renameFolders = array(
        "images" => "img",
        "javascript" => "js"
    );



    protected $siteLayout = array();


    // Force Extending class to define this method
    abstract protected function Initialize();

    public function __construct($blueprintInstance, $workingDirectory) {
        $this->blueprint = $blueprintInstance;
        $this->workingDirectory = $workingDirectory;

        $this->SetName("Default Project");

        $this->SetOutputPath("default");
        $this->SetViewPath("views");
        $this->SetTemplatePath("templates");
        $this->SetParserPath("parsers");
    }

    public function Generate()
    {
        $this->blueprint->Output(\Blueprint\MESSAGE, "Generating " . $this->name);


        // Remove old output folder
        if(is_dir($this->GetOutputPath()))
        {
            if ( !$this->blueprint->RemoveDirectory($this->GetOutputPath()) ) {
                $this->blueprint->Output(\Blueprint\ERROR, "There was an issue cleaning the output folder for \"" . $this->name . "\"");
                return;
            }
        }

        // Create new output folder
        if (!mkdir($this->GetOutputPath(), $this->GetDirectoryPermission(), true)) {
            $this->blueprint->Output(\Blueprint\ERROR, "Unable to create output folder for \"" . $this->name . "\" @ " . $this->GetOutputPath());
            return;
        }

        // Should we copy resources
        if ( $this->GetCopyResources() ) {

                $this->blueprint->RecursiveCopy(
                    $this->blueprint->BuildPath($this->workingDirectory, "resources"),
                    $this->GetOutputPath(),
                    $this->GetDirectoryPermission(),
                    $this->GetFilePermission(),
                    $this->GetIgnoreFiles());
        }

        // TODO: Handle rename of resources

        // Find All Views
        $viewsFiles = $this->blueprint->GetFiles($this->GetViewPath(), $this->GetIgnoreFiles());
        foreach($viewsFiles as $path)
        {
            $this->views[] = new \Blueprint\View($this, $path);
        }
        $this->blueprint->Output(\Blueprint\INFO, count($this->views) . " Views Found.");

        // Find All Templates
        $templateFiles = $this->blueprint->GetFiles($this->GetTemplatePath(), $this->GetIgnoreFiles());
        foreach($templateFiles as $path)
        {
            $file = end(explode(DIRECTORY_SEPARATOR, $path));
            $fileChunked = explode(".", $file);

            $this->templates[$fileChunked[0]] = new \Blueprint\Template($this, $path);
        }
        $this->blueprint->Output(\Blueprint\INFO, count($this->templates) . " Templates Found.");

        // Find All Parsers
        $parserFiles = $this->blueprint->GetFiles($this->GetParserPath(), $this->GetIgnoreFiles());
        foreach($parserFiles as $path)
        {
            require_once($path);

            // Determine class name (upper case first character of the file name)
            $className = str_replace(".php", "", end(explode(DIRECTORY_SEPARATOR, $path)));

            // Create parser object
            eval("\$this->parsers[" . $className . "] = new \\" . ucfirst($className) . "(\$this);");
        }

        // Process All Templates
        foreach($this->templates as $key => $template)
        {
            $template->Process();
        }

        // Process All Views
        foreach($this->views as $key => $view)
        {
            $view->Process();
        }

        // Output Views
        foreach($this->views as $key => $view)
        {
            $view->Generate();
        }
    }







    protected function SetCopyResources($should)
    {
        $this->copyResources = $should;
    }
    public function GetCopyResources()
    {
        if ( is_dir($this->blueprint->BuildPath($this->workingDirectory, "resources"))) {
            return $this->copyResources;
        } else {
            return false;
        }

    }
    protected function SetGlobalCompression($should)
    {
        $this->compression = $should;
    }
    public function GetGlobalCompression()
    {
        return $this->compression;
    }

    protected function SetName($name)
    {
        $this->name = $name;
    }
    public function GetName()
    {
        return $this->name;
    }
    public function GetParsers()
    {
        return $this->parsers;
    }
    public function GetParser($key)
    {
        return $this->parsers[$key];
    }

    public function GetRemoveTags()
    {
        return $this->removeTags;
    }
    public function SetRemoveTags($value)
    {
        $this->removeTags = $value;
    }

    public function GetFilePermission()
    {
        return $this->filePermission;
    }
    public function SetFilePermission($permission)
    {
        $this->filePermission = $permission;
    }
    public function GetDirectoryPermission()
    {
        return $this->directoryPermission;
    }
    public function SetDirectoryPermission($permission)
    {
        $this->directoryPermission = $permission;
    }
    protected function SetOutputPath($path) {

        if ( $this->blueprint->IsAbsolutePath($path) )
        {
            $this->outputPath = $path;
        }
        else
        {
            $this->outputPath = $this->blueprint->BuildPath($this->blueprint->GetRootDirectory(), "output", $path);
        }
    }
    protected function SetTemplatePath($path) {

        if ( $this->blueprint->IsAbsolutePath($path) )
        {
            $this->templatePath = $path;
        }
        else
        {
            $this->templatePath = $this->blueprint->BuildPath($this->workingDirectory, $path);
        }
    }
    protected function SetParserPath($path) {

        if ( $this->blueprint->IsAbsolutePath($path) )
        {
            $this->parserPath = $path;
        }
        else
        {
            $this->parserPath = $this->blueprint->BuildPath($this->workingDirectory, $path);
        }
    }

    protected function SetViewPath($path) {
        if ( $this->blueprint->IsAbsolutePath($path) )
        {
            $this->viewPath = $path;
        }
        else
        {
            $this->viewPath = $this->blueprint->BuildPath($this->workingDirectory, $path);
        }
    }
    public function GetViewPath()
    {
        return $this->viewPath;
    }
    public function GetParserPath()
    {
        return $this->parserPath;
    }
    public function GetOutputPath()
    {
        return $this->outputPath;
    }

    public function GetTemplatePath()
    {
        return $this->templatePath;
    }

    public function AddIgnore($filename)
    {
        if ( !in_array($filename, $this->ignoreFiles) ) {
            $this->ignoreFiles[] = $filename;
        }
    }
    public function GetIgnoreFiles()
    {
        return $this->ignoreFiles;
    }

    public function ShouldCompress($relativePath)
    {
        if ( !$compression ) return false;
        if ( in_array($relativePath, $this->ignoreCompression) ) {
            return false;
        }
        return true;
    }
    public function ShouldRenameFolder($relativePath)
    {

    }
}