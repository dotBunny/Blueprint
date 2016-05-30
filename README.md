# Blueprint
A static page generation system which allows for dynamic content repurposing.


# Configuration

## Project File

A project file defines the project and all of its surrounding settings.

```php
class Website extends Blueprint\Project 
{
    public function Initialize()
    {
        $this->OutputPath = "../../html/";
    }    
}
```

### Project::Initialize()
A place for all settings to be set and functions to be called which define how the project should be parsed.

### Project::AddIgnore()
Add a file to be ignored by the sites processors. It will not be copied or processed.

### Project::Replace()
Add global replace options for {TAG}'s


# Generating Content


php blueprint.php build ./tests




## Build
Build compiles a working copy of the site to a target directory, this is useful for checking how files will look when compiled. This folder is wiped clean every build so that it is the abosulte latest version of all files involved.

## Deploy
This takes the build folder and compares it to a deployment folder and only makes the changes it finds, copies over new files, and deletes files not found in the build folder. This is useful for things where you sync deployments.


## Blueprint Definition

## Template Definition

<!-- BLUEPRINT parsers="general" destination="test2/index.html" subtitle="page additive" -->




<!-- START header -->
