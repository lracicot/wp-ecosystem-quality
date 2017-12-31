<?php

namespace Analyzer\NamespacePollution;

use Analyzer\{PhpFileContentMapper, Analyzer};

class NamespacePollution extends Analyzer
{
    public function analyze($analyze_dir, $results_dir = false)
    {
        $plugin = basename($analyze_dir);
        $this->logger->info('Analysing global namespace pollution for ' . $plugin . "...\n");

        $functions = 0;
        exec('php -d memory_limit=1024M vendor/bin/phploc ' . $analyze_dir . ' \
                --exclude ' . $analyze_dir . '/vendor | grep "Named Functions"', $functions);

        return trim(substr($functions[0], strlen('Lines of Code (LOC)')+8));
    }
}
