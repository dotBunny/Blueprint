<?php

function __autoload($class) {

    // Don't allow for double initialization
    if ( class_exists($class, false ) ) {
        return;
    }

    // Check for standard class location first
    $path = getcwd() . DIRECTORY_SEPARATOR . "classes" . DIRECTORY_SEPARATOR . end(explode('\\', $class)) . ".php";

    if ( file_exists( $path ) )
    {
        require_once($path);
    }
}

// Initialize Blueprint with passed Arguements
$BP = new Blueprint\Core($argv);

$BP->Run();