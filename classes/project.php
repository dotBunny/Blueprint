<?php

namespace Blueprint;

abstract class Project
{
    public $templates = array();

    protected $views = array();
    protected $parsers = array();

    private $keyValues = array();

    private $currentView;
    public function getCurrentView()
    {
        return $this->currentView;
    }


    private $compression = true;
    private $removeTags = true;

    private $removeExtraDeployFiles = true;



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
        $this->BuildPath = "build";
    }

    public function __get($name)
    {
        return $this->keyValues[$name];;
    }

    public function __set($key, $value)
    {
        switch($key)
        {
            case "BuildPath":
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

    public function Initialize()
    {

    }

    public function PostInitialize()
    {
        // Find All Templates
        $templateFiles = Core::GetFiles($this->TemplatePath, $this->getIgnoreFiles());
        Core::Output(INFO, "Searching " . $this->TemplatePath . " for Templates ...");
        if (!is_null($templateFiles) && !empty($templateFiles))
        {
            foreach($templateFiles as $path)
            {
                $file = end(explode(DIRECTORY_SEPARATOR, $path));
                $fileChunked = explode(".", $file);

                $this->templates[strtolower($fileChunked[0])] = new Template($this, $path);
            }
        }
        Core::Output(INFO, count($this->templates) . " Templates Found.");


        // Find All Parsers
        $parserFiles =  Core::GetFiles($this->ParsersPath, $this->getIgnoreFiles());
        if (!is_null($parserFiles) && !empty($parserFiles))
        {
            foreach($parserFiles as $path)
            {
                Core::Output(INFO, "Including " . $path);
                require_once($path);

                // Determine class name (upper case first character of the file name)
                $className = strtolower(str_replace(".php", "", end(explode(DIRECTORY_SEPARATOR, $path))));

                // Create parser object
                eval("\$this->parsers[" . $className . "] = new \\" . ucfirst($className) . "(\$this);");
            }
        }

        // Load a default replacer
        $this->parsers["SystemReplacer"] = new Replace($this);
        $this->parsers["SystemReplacer"]->Set("BLUEPRINT_GENERATOR", NAME . " " . REVISION);

    }

    public function Update()
    {
        Core::Output(MESSAGE, "Updating " . $this->name);

        // Check we have a site input folde
        if ( !is_dir($this->SitePath) ) {
            Core::Output(ERROR, "The sites input folder is invalid. (" . $this->SitePath . ")");
            return;
        }

        // Clear out views before the copy happens (which finds them)
        $this->views = array();

        // Find All Views
        $this->FindViews($this->SitePath, $this->getIgnoreFiles());


        // Process All Views
        foreach($this->views as $key => $view)
        {
            $this->currentView = $this->views[$key];
            $view->Update();
        }

    }

    public function Build()
    {
        Core::Output(MESSAGE, "Building " . $this->name);

        // Remove old output folder
        if(is_dir($this->BuildPath))
        {
            if ( !Core::RemoveDirectory($this->BuildPath) ) {
                Core::Output(ERROR, "There was an issue cleaning the build folder for \"" . $this->Name . "\"");
                return;
            }
        }

        // Create new output folder
        if (!mkdir($this->BuildPath, $this->getDirectoryPermission(), true)) {
            Core::Output(ERROR, "Unable to create build folder for \"" . $this->Name . "\" @ " . $this->BuildPath);
            return;
        }

        // Check we have a site input folde
        if ( !is_dir($this->SitePath) ) {
            Core::Output(ERROR, "The sites input folder is invalid. (" . $this->SitePath . ")");
            return;
        }

        // Clear out views before the copy happens (which finds them)
        $this->views = array();

        // Find All Views
        $this->ProcessSiteFolder(
                $this->SitePath,
                $this->BuildPath,
                $this->getDirectoryPermission(),
                $this->getFilePermission(),
                $this->getIgnoreFiles());

        Core::Output(INFO, count($this->views) . " Views Found.");

        // Process All Views
        foreach($this->views as $key => $view)
        {
            $this->currentView = $this->views[$key];
            $view->Process();
        }

        // Output Views
        foreach($this->views as $key => $view)
        {

            $this->currentView = $this->views[$key];
            $view->Generate();
        }
    }

    public function Deploy()
    {
        // Get Build Files
        $buildFiles = $this->GetFileList($this->BuildPath, $this->getIgnoreFiles());

        // Remove Start Of Path
        for($i = 0; $i < count($buildFiles); $i++)
        {
            $buildFiles[$i] = str_replace($this->BuildPath, "", $buildFiles[$i]);
        }

        // Get "Current" Output Files
        $outputFiles = $this->GetFileList($this->OutputPath, $this->getIgnoreFiles());
        for($i = 0; $i < count($outputFiles); $i++)
        {
            $outputFiles[$i] = str_replace($this->OutputPath, "", $outputFiles[$i]);
        }

        // Remove files not in build
        if ( $this->removeExtraDeployFiles )
        {
            foreach ($outputFiles as $outputFile)
            {
                if ( !in_array ( $outputFile, $buildFiles, false) )
                {
                    Core::Output(INFO, "Removing " . $this->OutputPath . $outputFile . " as it does not exist in build.");
                    unlink($this->OutputPath . $outputFile);
                }
            }
        }

        // Check and copy
        foreach($buildFiles as $buildFile)
        {
            $buildFileHash = hash_file("md5", $this->BuildPath . $buildFile);
            $outputFileHash = 0;

            // Check Stamp
            if ( file_exists($this->OutputPath . $buildFile) )
            {
                $outputFileHash = hash_file("md5", $this->OutputPath . $buildFile);
            }

            // If the stamp is newer, and hash is different
            if (($outputFileHash != $buildFileHash))
            {

                Core::Output(INFO, "Deploying " . $this->OutputPath . $buildFile . " ...");
                copy($this->BuildPath . $buildFile, $this->OutputPath . $buildFile);
            }
        }
    }

    private function GetFileList($source, $ignoreFiles)
    {
        $files = array();

         // Check for symlinks
        if (is_link($source)) {
            // No support for symlinks
            $files = array_merge($files, $this->GetFilesList(symlink(readlink($source)), $ignoreFiles));
        }


        // If it truly is a file
        if (is_file($source))
        {
            array_push($files, $source);
        }

        // Make destination directory
        if (is_dir($source))
        {
            // Loop through the folder
            $dir = dir($source);

            while (false !== $entry = $dir->read())
            {
                // Skip pointers
                if ($entry == '.' || $entry == '..' || in_array($entry, $ignoreFiles)) {
                    continue;
                }

                $files = array_merge($files, $this->GetFileList("$source/$entry", $ignoreFiles));
            }

        }

        return $files;
    }


    private function FindViews($source, $ignoreFiles)
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
        }

        // Loop through the folder
        if ( is_dir($source) )
        {
            $dir = dir($source);

            while (false !== $entry = $dir->read()) {
                // Skip pointers
                if ($entry == '.' || $entry == '..' || in_array($entry, $ignoreFiles)) {
                    continue;
                }

                // Deep copy directories
                $this->FindViews("$source/$entry", $ignoreFiles);
            }

            // Clean up
            $dir->close();
        }
        return true;

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


			// Is this file a compress-ible file?
			if ( $this->getGlobalCompression())
			{
				print $source . "\n\r";
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
        return $this->parsers[trim($key)];
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

    public function getRemoveExtraDeployFiles()
    {
        return $this->removeExtraDeployFiles;
    }
    public function setRemoveExtraDeployFiles($value)
    {
        $this->getRemoveExtraDeployFiles = $value;
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
    public function AddIgnoreCompression($relativePath)
    {
	    if ( !in_array ($relativePath, $this->ignoreCompression) ) {
		    $this->ignoreCompression[] = $relativePath;
	    }
    }

    public function Replace($key, $value)
    {
        if (is_null($this->parsers["replace"]))
        {
            // No need to namespace it as its not in a sub
            $this->parsers["replace"] = new Replace($this);
        }
        $this->parsers["replace"]->Set($key, $value);
    }
    public function GetReplaceValue($key)
    {
        return $this->parsers["replace"]->Get($key);
    }
}

class DefaultProject extends Project { }