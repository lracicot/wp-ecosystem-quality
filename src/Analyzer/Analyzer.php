<?php

namespace Analyzer;


abstract class Analyzer
{
  protected $logger;

  public function __construct($logger)
  {
      $this->logger = $logger;
  }

  abstract public function analyze($analyze_dir, $results_dir = false);
}
