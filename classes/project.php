<?php

namespace Blueprint;

abstract class Project
{
    public $templates = array();

    protected $views = array();
    private $currentView;

    protected $parsers = array();

    private $keyValues = array();



    private $compression = true;
    private $removeTags = true;

    private $directoryPermission = 0777;
    private $filePermission = 0666;

    private $ignoreFiles = array(".DS_Store", ".git", ".svn");

    public function __construct($rootDirectory, $workingDirectory) {

        // Set all our defaults up in the house in the fancy KeyValue store
        $this->WorkingDirectory= $workingDirectory;
        $this->RootDirectory = $rootDirectory;
        $this->Name = "Default Project";
        $this->OutputPath = "output";
        $this->SitePath= "site";
        $this->TemplatePath = "templates";
        $this->ParsersPath = "parsers";
    }

    public function __get($name)
    {
        return $this->keyValues[$name];;
    }

    public function __set($key, $value)
    {
        switch($key)
        {
            case "OutputPath":
            case "SitePath":
            case "ParsersPath":
            case "TemplatePath":
                if ( Core::IsAbsolutePath($value) )
                {
                    $this->keyValues[$key] = $path;
                }
                else
                {
                    $this->keyValues[$key] = Core::BuildPath($this->WorkingDirectory, $value);
                }
                break;
            default:
                $this->keyValues[$key] = $value;
                break;
        }
    }

    public function GetCurrentView()
    {
        return $this->currentView;
    }
    public function Initialize()
    {

    }

    public function PostInitialize()
    {
        // Find All Templates
        $templateFiles = Core::GetFiles($this->TemplatePath, $this->getIgnoreFiles());
        Core::Output(INFO, "Searching " . $this->TemplatePath . " for Templates ...");
        foreach($templateFiles as $path)
        {
            $file = end(explode(DIRECTORY_SEPARATOR, $path));
            $fileChunked = explode(".", $file);

            $this->templates[strtolower($fileChunked[0])] = new Template($this, $path);
        }
        Core::Output(INFO, count($this->templates) . " Templates Found.");


        // Find All Parsers
        $parserFiles =  Core::GetFiles($this->ParsersPath, $this->getIgnoreFiles());
        foreach($parserFiles as $path)
        {
            Core::Output(INFO, "Including " . $path);
            require_once($path);

            // Determine class name (upper case first character of the file name)
            $className = strtolower(str_replace(".php", "", end(explode(DIRECTORY_SEPARATOR, $path))));

            // Create parser object
            eval("\$this->parsers[" . $className . "] = new \\" . ucfirst($className) . "(\$this);");
        }

        // Load a default replacer
        $this->parsers["SystemReplacer"] = new Replace($this);
        $this->parsers["SystemReplacer"]->Set("BLUEPRINT_GENERATOR", NAME . " " . REVISION);

    }

    public function Generate()
    {
        Core::Output(MESSAGE, "Generating " . $this->name);

        // Remove old output folder
        if(is_dir($this->OutputPath))
        {
            if ( !Core::RemoveDirectory($this->OutputPath) ) {
                Core::Output(ERROR, "There was an issue cleaning the output folder for \"" . $this->Name . "\"");
                return;
            }
        }

        // Create new output folder
        if (!mkdir($this->OutputPath, $this->getDirectoryPermission(), true)) {
            Core::Output(ERROR, "Unable to create output folder for \"" . $this->Name . "\" @ " . $this->OutputPath);
            return;
        }

        // Clear out views before the copy happens (which finds them)
        $this->views = array();

        // Find All Views
        $this->ProcessSiteFolder(
                $this->SitePath,
                $this->OutputPath,
                $this->getDirectoryPermission(),
                $this->getFilePermission(),
                $this->getIgnoreFiles());

        Core::Output(INFO, count($this->views) . " Views Found.");

        // Process All Views
        foreach($this->views as $key => $view)
        {
            $currentView = $key;
            $view->Process();
        }

        // Output Views
        foreach($this->views as $key => $view)
        {
            $view->Generate();
        }
    }

    private function ProcessSiteFolder($source, $dest, $folderPermissions, $filePermissions, $ignoreFiles = null)
    {
         // Check for symlinks
        if (is_link($source)) {
            // No support for symlinks
            return true;
//            return symlink(readlink($source), $dest);
        }

        // If it truly is a file
        if (is_file($source))
        {
            // Check file is a Blueprint, ignore if it is and add it to the views, else copy it.
            $check = strpos(file_get_contents($source), TAG_START . TAG_BLUEPRINT);

            if ($check !== false)
            {
                $this->views[] = new View($this, $source);
                return true;
            }

            return copy($source, $dest);
        }

        // Make destination directory
        if (!is_dir($dest)) {
            mkdir($dest, $folderPermissions, true);
        }

        // Loop through the folder
        $dir = dir($source);
        while (false !== $entry = $dir->read()) {
            // Skip pointers
            if ($entry == '.' || $entry == '..' || in_array($entry, $ignoreFiles)) {
                continue;
            }

            // Deep copy directories
            $this->ProcessSiteFolder("$source/$entry", "$dest/$entry", $folderPermissions, $filePermissions, $ignoreFiles);
        }

        // Clean up
        $dir->close();
        return true;
    }









    public function GetParser($key)
    {
        return $this->parsers[$key];
    }




    public function setGlobalCompression($should)
    {
        $this->compression = $should;
    }
    public function getGlobalCompression()
    {
        return $this->compression;
    }








    public function getParsers()
    {
        return $this->parsers;
    }
    public function getRemoveTags()
    {
        return $this->removeTags;
    }
    public function setRemoveTags($value)
    {
        $this->removeTags = $value;
    }

    public function getFilePermission()
    {
        return $this->filePermission;
    }
    public function setFilePermission($permission)
    {
        $this->filePermission = $permission;
    }
    public function getDirectoryPermission()
    {
        return $this->directoryPermission;
    }
    public function setDirectoryPermission($permission)
    {
        $this->directoryPermission = $permission;
    }

    public function getIgnoreFiles()
    {
        return $this->ignoreFiles;
    }




    public function AddIgnore($filename)
    {
        if ( !in_array($filename, $this->ignoreFiles) ) {
            $this->ignoreFiles[] = $filename;
        }
    }

    public function ShouldCompress($relativePath)
    {
        if ( !$compression ) return false;
        if ( in_array($relativePath, $this->ignoreCompression) ) {
            return false;
        }
        return true;
    }
}

class DefaultProject extends Project { }