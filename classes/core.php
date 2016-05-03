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

define(__NAMESPACE__ ."\WINDOWS", 6);
define(__NAMESPACE__ ."\UNIX", 7);

class Core
{
    public static $ErrorCount = 0;
    public static $WarningCount = 0;
    public static $CanRun = true;
    public static $Platform = UNIX;


    private $arguments = array();
    private $projects = array();

    private $rootDirectory;

    private $mode = GENERATE;


    /**
    * Builds a file path with the appropriate directory separator.
    * @param string $segments,... unlimited number of path segments
    * @return string Path
    */
    function __construct($passedArguments)
    {
        // Platform stuff
        if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), 'windows') !== false) {
            Core::$Platform = WINDOWS;
            system("cls");
        } else {
            Core::$Platform = UNIX;
            system("clear");
        }


        // Assign our root directory
        $this->rootDirectory = getcwd();

        $this->SetupEnvironment();

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
                Core::Output(ERROR, "You must provide an action to do with Blueprint.");

                 // Output information on how to run Blueprint with project files/paths
                Core::Output(INFO, "Acceptable actions are \"update\" which pushes template updates to views, and \"generate\" which will update the output.");

                // Stop any further
                Core::$CanRun = false;
                return;
        }

        if ( is_null($this->arguments[2]) )
        {
            // Output error
            Core::Output(ERROR, "You must provide a project path to generate from.");

            // Output information on how to run Blueprint with project files/paths
            Core::Output(INFO, "By default we look in the local project folder, however you are able to provide full paths to a folder and it will use it instead. An example local project would be to use \"tests\" which will generate based on the test framework packaged with Blueprint in the project folder (" . BuildPath($this->rootDirectory, "projects", "tests") . ").");

            // Stop any further
            Core::$CanRun= false;
            return;
        }
        else
        {
            // Load Classes
            $this->LoadClasses();

            $this->LoadParsers();

            // Load Projects
            Core::Output(MESSAGE, "Loading Projects ...");
            for($i = 2; $i < count($this->arguments); $i++)
            {
                $this->LoadProject(realpath($this->arguments[$i]));
            }
        }
    }


    public function getRootDirectory()
    {
        return $this->rootDirectory;
    }


    public static function BuildPath() {
        return join(DIRECTORY_SEPARATOR, func_get_args($segments));
    }

    public static function GetFiles($directory, $ignoreFiles = null)
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

    public static function IsAbsolutePath($path)
    {
        if($path === null || $path === '') return false;
        return $path[0] === DIRECTORY_SEPARATOR || preg_match('~\A[A-Z]:(?![^/\\\\])~i',$path) > 0;
    }

    public static function Output($type, $message, $clear = false)
    {
        switch($type) {
            case ERROR:
                Core::$ErrorCount++;
                print "\033[1;31m" . $message . "\033[0m\n\r";
                break;
            case INFO:
                print "\033[37m" . $message . "\033[0m\n\r";
                break;
            case WARNING:
                Core:$WarningCount++;
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


    public static function RemoveDirectory($directory)
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
                        $path = Core::BuildPath($directory, $object);
                        if (is_dir($path))
                        {
                            Core::RemoveDirectory($path);
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
            Core::Output(INFO, "An error occurred removing directory: " . $directory);
            Core::Output(INFO, $e);
            return false;
        }
        return true;
    }

    public static function SetPath(&$target, $path, $defaultLocation)
    {
        if ( Core::IsAbsolutePath($path) )
        {
            $target = $path;
        }
        else
        {
            $target = Core::BuildPath($defaultLocation, $path);
        }

    }




    public function Run()
    {
        if ( !Core::$CanRun ) {
            Core::Output(INFO, "Critical errors found, aborting execution.");
            return;
        }

        if ( $this->mode == GENERATE ) {
            $this->Generate();
        } elseif ( $this->mode == UPDATE ) {
            $this->Update();
        }
    }

    private function Generate()
    {
       foreach ($this->projects as $key => $project)
       {
            $project->Generate();
       }

        // Output Message Counts
        if ( Core::$ErrorCount > 0 ) {
            Core::Output(ERROR, "Finished Generate with " . Core::$ErrorCount-- . " Error[s], and " . Core::$WarningCount . " Warning[s].");
        } elseif ( Core::$WarningCount > 0 ) {
            Core::Output(WARNING, "Finished Generate with " . Core::$ErrorCount . " Error[s], and " . Core::$WarningCount-- . " Warning[s].");
        }
    }

    private function LoadClasses()
    {
        Core::Output(MESSAGE, "Loading Classes ...");

        // Include abstract project class
        require_once(Core::BuildPath($this->rootDirectory, "classes", "tag.php"));
        require_once(Core::BuildPath($this->rootDirectory, "classes", "project.php"));
        require_once(Core::BuildPath($this->rootDirectory, "classes", "template.php"));
        require_once(Core::BuildPath($this->rootDirectory, "classes", "view.php"));
        require_once(Core::BuildPath($this->rootDirectory, "classes", "parser.php"));
    }

    private function LoadParsers()
    {
        Core::Output(MESSAGE, "Loading Default Parsers ...");

        // Include abstract project class
        require_once(Core::BuildPath($this->rootDirectory, "parsers", "replace.php"));
    }



    private function LoadProject($path)
    {
        Core::Output(INFO, "Locating project @ ". $path . "...");

        $projectPath = $path;

        // Check Pathing
        if (is_dir($path)) {
        }
        elseif (is_dir(Core::BuildPath($this->rootDirectory, "projects", $path)))
        {
            $projectPath = Core::BuildPath($this->rootDirectory, "projects", $path);

        }
        else {
            Core::Output(ERROR, "Invalid \"" . $path . "\" Project");
            Core::Output(INFO, "A local or remote project was not found for the \"" . $path . "\" argument, it can be either a reference to a local project, or a full path to a remote project.");
            return;
        }

        // Check for Project file
        $projectName = end(explode(DIRECTORY_SEPARATOR, $projectPath));
        $projectFilePath = Core::BuildPath($projectPath, $projectName . ".php");

        if ( file_exists($projectFilePath) ) {

            // Include our project define
            Core::Output(INFO, "Found \"" . $projectName . "\" @ " . $projectPath);
            require_once($projectFilePath);

            // Create class object
            Core::Output(INFO, "Creating dynamic project class for " . $projectName . "...");
            eval("\$this->projects[" . $projectName . "] = new \\" . ucfirst($projectName) . "(\"" . $this->rootDirectory . "\",\"" . $projectPath . "\");");
        }
        else
        {
            // Create our defaultclass object
            Core::Output(INFO, "Using the DefaultProject class");
            $this->projects[$projectName] = new DefaultProject($this->rootDirectory,$projectPath);
        }
        // Initialize object
        $this->projects[$projectName]->Initialize();

        // Post initialize options
        $this->projects[$projectName]->PostInitialize();
    }

    private function SetupEnvironment()
    {
         date_default_timezone_set("America/Toronto");
    }



    private function Update()
    {

    }


 }