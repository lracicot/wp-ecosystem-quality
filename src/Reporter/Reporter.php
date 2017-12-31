<?php

namespace Reporter;

abstract class Reporter
{
    protected $logger;

    public function __construct($logger)
    {
        $this->logger = $logger;
    }

    abstract public function report($analyze_dir);
}
