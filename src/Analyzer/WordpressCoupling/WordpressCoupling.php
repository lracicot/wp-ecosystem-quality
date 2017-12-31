<?php

namespace Analyzer\WordpressCoupling;

use Analyzer\{PhpFileContentMapper, Analyzer};


class WordpressCoupling extends Analyzer
{
  public function analyze($analyze_dir, $results_dir = false)
  {
    $plugin = basename($analyze_dir);
    $this->logger->info('Analysing wp_coupling for ' . $plugin . "...\n");

    // Get a list of strings we want to find
    $termsf = file(__DIR__.'/function_list');
    $termsc = file(__DIR__.'/class_list');
    $terms = array_merge($termsf, $termsc);

    $mapper = new PhpFileContentMapper($analyze_dir);

    return $mapper->map(function ($str) use ($terms) {
        return array_reduce($terms, function ($sum, $term) use ($str) {
            return $sum + substr_count($str, trim($term));
        }, 0);
    });
  }
}
