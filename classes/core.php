<?php

namespace Blueprint;

// Localized Defines
define(__NAMESPACE__ ."\NAME", "Blueprint");
define(__NAMESPACE__ ."\REVISION", 2);

// Output Categories
define(__NAMESPACE__ ."\ERROR", 0);
define(__NAMESPACE__ ."\INFO", 1);
define(__NAMESPACE__ ."\WARNING", 2);
define(__NAMESPACE__ ."\MESSAGE", 3);

// Run Categories
define(__NAMESPACE__ ."\GENERATE", 4);
define(__NAMESPACE__ ."\UPDATE", 5);


class Core
{
    private $canRun = true;


    private $arguments = array();
    private $projects = array();
    private $rootDirectory;



    private $mode = GENERATE;

    private $errorCount = 0;
    private $warningCount = 0;

    /**
    * Builds a file path with the appropriate directory separator.
    * @param string $segments,... unlimited number of path segments
    * @return string Path
    */
    function __construct($passedArguments)
    {
        // Assign our root directory
        $this->rootDirectory = getcwd();

        $this->arguments = $passedArguments;

        switch($this->arguments[1])
        {   case "views":
            case "update":
                $this->mode = UPDATE;
                break;
            case "generate":
            case "output":
                $this->mode = GENERATE;
                break;
            default:
                 $this->Output(ERROR, "You must provide an action to do with Blueprint.");

                 // Output information on how to run Blueprint with project files/paths
                $this->Output(INFO, "Acceptable actions are \"update\" which pushes template updates to views, and \"generate\" which will update the output.");

                // Stop any further
                $this->canRun = false;
                return;
        }

        if ( is_null($this->arguments[2]) )
        {
            // Output error
            $this->Output(ERROR, "You must provide a project path to generate from.");

            // Output information on how to run Blueprint with project files/paths
            $this->Output(INFO, "By default we look in the local project folder, however you are able to provide full paths to a folder and it will use it instead. An example local project would be to use \"tests\" which will generate based on the test framework packaged with Blueprint in the project folder (" . $this->BuildPath($this->rootDirectory, "projects", "tests") . ").");

            // Stop any further
            $this->canRun = false;
            return;
        }
        else
        {
            // Load Classes
            $this->LoadClasses();

            // Load Projects
            $this->Output(MESSAGE, "Loading Projects ...");
            for($i = 2; $i < count($this->arguments); $i++)
            {
                $this->LoadProject($this->arguments[$i]);
            }
        }
    }

    static public function RecursiveCopy($source, $dest, $folderPermissions, $filePermissions, $ignoreFiles = null)
    {
         // Check for symlinks
        if (is_link($source)) {
            return symlink(readlink($source), $dest);
        }

        // Simple copy for a file
        if (is_file($source)) {
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
            self::RecursiveCopy("$source/$entry", "$dest/$entry", $folderPermissions, $filePermissions, $ignoreFiles);
        }

        // Clean up
        $dir->close();
        return true;
    }

    function GetFiles($directory, $ignoreFiles = null)
    {
        if ( !is_dir($directory) ) return false;
        $items =new \RecursiveDirectoryIterator($directory);

        $files = array();
        foreach (new \RecursiveIteratorIterator($items) as $filename =>$current) {
            if (!is_dir($filename) && !in_array($current->GetFileName(), $ignoreFiles))
            {
                $files[] = $filename;
            }

        }
        return $files;
    }

    static function RemoveDirectory($directory)
    {
        try
        {
            if (is_dir($directory))
            {

                $objects = scandir($directory);

                foreach ($objects as $object)
                {
                    if ($object != "." && $object !="..")
                    {
                        $path = self::BuildPath($directory, $object);
                        if (is_dir($path))
                        {
                                self::RemoveDirectory($path);
                        }
                        else
                        {
                                unlink($path);
                        }
                    }
                }

                reset($objects);
                rmdir($directory);
            }

        } catch ( Exception $e) {
            $this->Output(INFO, "Interesting");
            $this->Output(INFO, $e);
            return false;
        }
        return true;
    }

    static function BuildPath() {
        return join(DIRECTORY_SEPARATOR, func_get_args($segments));
    }

    function IsAbsolutePath($path)
    {
        if($path === null || $path === '') return false;
        return $path[0] === DIRECTORY_SEPARATOR || preg_match('~\A[A-Z]:(?![^/\\\\])~i',$path) > 0;
    }

    function GetRootDirectory()
    {
        return $this->rootDirectory;
    }



    function Run()
    {
        if ( !$this->canRun ) {
            $this->Output(INFO, "Critical errors found, aborting execution.");
            return;
        }

        if ( $this->mode == GENERATE ) {
            $this->Generate();
        } elseif ( $this->mode == UPDATE ) {
            $this->Update();
        }
    }

    function Output($type, $message, $clear = false)
    {
        switch($type) {
            case ERROR:
                $this->errorCount++;
                print "\033[1;31m" . $message . "\033[0m\n\r";
                break;
            case INFO:
                print "\033[37m" . $message . "\033[0m\n\r";
                break;
            case WARNING:
                $this->warningCount++;
                print "\033[0;33m" . $message . "\033[0m\n\r";
                break;
            case MESSAGE:
                print "\033[32m" . $message . "\033[0m\n\r";
                break;
            default:
                print "\033[0m" . $message . "\033[0m\n\r";
                break;
        }
    }

    public function FindNextTag($tag, $content, $offset = 0)
    {
        $firstIndex = strpos($content, "<!-- " . $tag, $offset);
        $endIndex = strpos($content, " -->", $firstIndex);

        return substr($content, $firstIndex, ($endIndex - $firstIndex) +  4);
    }

    public function GetTagInfo($tag)
    {
        $returnArray = array();
        $returnArray["baseContent"] = $tag;

        // Clean up tag
        $returnArray["content"] = trim(str_replace("<!--", "", str_replace("-->", "", $tag)));

        // Determine if its one of ours
        $chunks = explode(" ", $returnArray["content"]);

        $returnArray["mode"] = strtolower($chunks[0]);

        switch($returnArray["mode"])
        {
            case "blueprint":
            case "template":
                for($i = 1; $i < count($chunks); $i++) {
                    $entry = split("=", $chunks[$i]);
                    $returnArray[strtolower($entry[0])] = str_replace("\"", "", $entry[1]);
                }
                $returnArray["valid"] = true;
                break;
            default:
                $returnArray["valid"] = false;
                break;
        }

        return $returnArray;

        //<!-- BLUEPRINT TYPE="view" NAME="home" URI="/" OUTPUT="index.html" PARSERS="global" -->
        //<!-- BLUEPRINT TYPE="template" NAME="footer" PARSERS="global,footer" -->
        //<!-- TEMPLATE NAME="footer" ACTION="start" -->
    }


    private function Generate()
    {
       foreach ($this->projects as $key => $project)
       {
            $project->Generate();
       }

        // Output Message Counts
        if ( $this->errorCount > 0 ) {
            $this->Output(ERROR, "Finished Generate with " . $this->errorCount-- . " Error[s], and " . $this->warningCount . " Warning[s].");
        } elseif ( $this->warningCount > 0 ) {
            $this->Output(WARNING, "Finished Generate with " . $this->errorCount . " Error[s], and " . $this->warningCount-- . " Warning[s].");
        }
    }

    private function Update()
    {

    }

    private function LoadClasses()
    {
        $this->Output(MESSAGE, "Loading Classes ...");
        // Include abstract project class
        require_once($this->BuildPath($this->rootDirectory, "classes", "project.php"));
        require_once($this->BuildPath($this->rootDirectory, "classes", "view.php"));
        require_once($this->BuildPath($this->rootDirectory, "classes", "template.php"));
                require_once($this->BuildPath($this->rootDirectory, "classes", "parser.php"));
    }

    private function LoadProject($path)
    {
        $projectPath = $path;

        // Check Pathing
        if (is_dir($path)) {
        }
        elseif (is_dir($this->BuildPath($this->rootDirectory, "projects", $path)))
        {
            $projectPath = $this->BuildPath($this->rootDirectory, "projects", $path);
        }
        else {
            $this->Output(ERROR, "Invalid \"" . $path . "\" Project");
            $this->Output(INFO, "A local or remote project was not found for the \"" . $path . "\" argument, it can be either a reference to a local project, or a full path to a remote project.");
            return;
        }

        // Check for Project file
        $className = end(explode(DIRECTORY_SEPARATOR, $projectPath));
        $classPath = $this->BuildPath($projectPath, $className . ".php");

        // Format name afte
        $className = ucfirst($className);

        if ( file_exists($classPath) ) {

            // Include our project define
            $this->Output(INFO, "Found \"" . $className . "\" @\n\r" . $projectPath);
            require_once($classPath);

            // Create class object
            eval("\$this->projects[" . $className . "] = new \\" . $className . "(\$this,\"" . $projectPath . "\");");

            // Initialize object
            $this->projects[$className]->Initialize();
        }
        else {

            $this->Output(ERROR, "No Project File Was Found @ " . $classPath);
            $this->Output(INFO, "A file of the same name as the project folder is required to define the generation settings. In this case, Blueprint was looking for a file called \"" . lcfirst($className) . ".php\" in the \"" . $projectPath . "\" folder.");
            return;
        }
    }

    }