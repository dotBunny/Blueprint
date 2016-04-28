<?php

namespace Blueprint;

abstract class Parser
{
    protected $project;

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