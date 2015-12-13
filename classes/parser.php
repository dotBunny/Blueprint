<?php

namespace Blueprint;

abstract class Parser
{
    private $project;

    public function __construct(&$project) {
        $this->project = $project;
        $this->Initialize();
    }

    public function Initialize()
    {

    }

    public function Process($content)
    {
        return $content;
    }
}