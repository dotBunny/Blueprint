<?php

namespace Blueprint;

abstract class Parser
{
    private $project;
    protected $name;

    public function __construct($project) {
        $this->project = $project;
    }

    public function Process($content)
    {
        return $content;
    }
}