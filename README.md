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


php blueprint.php generate ./tests



## Blueprint Definition

## Template Definition

<!-- BLUEPRINT parsers="general" destination="test2/index.html" subtitle="page additive" -->




<!-- START header -->