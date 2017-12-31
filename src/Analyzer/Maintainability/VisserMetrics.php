<?php

namespace Analyzer\Maintainability;

use Analyzer\Analyzer;

class VisserMetrics extends Analyzer
{
    public function analyze($analyze_dir, $results_dir = false, $reportFile = false)
    {
        $plugin = basename($analyze_dir);
        if (!$reportFile) {
            $reportFile = $plugin;
        }

        if (!file_exists($results_dir . '/phpmd/')) {
            mkdir($results_dir . '/phpmd/', 0777, true);
        }

        if (!file_exists($results_dir . '/phpcpd/')) {
            mkdir($results_dir . '/phpcpd/', 0777, true);
        }

        if (!file_exists($results_dir . '/sloccount/')) {
            mkdir($results_dir . '/sloccount/', 0777, true);
        }

        if (!file_exists($results_dir . '/tests/')) {
            mkdir($results_dir . '/tests/', 0777, true);
        }

        var_dump('php -d memory_limit=2048M vendor/bin/phpmd ' . $analyze_dir . ' xml phpmd.xml \
                --reportfile ' . $results_dir . '/phpmd/' . $reportFile . '.xml \
                --exclude ' . $analyze_dir . 'vendor');

        // Analyse criteria 1, 2, 4, 5, 11
        // Validity: not all possible smells?
        if (!file_exists($results_dir . '/phpmd/' . $reportFile . '.xml')) {
            $this->logger->info('Analysing phpmd for ' . $plugin . "...\n");
            exec('php -d memory_limit=2048M vendor/bin/phpmd ' . $analyze_dir . ' xml phpmd.xml \
                    --reportfile ' . $results_dir . '/phpmd/' . $reportFile . '.xml \
                    --exclude ' . $analyze_dir . 'vendor');
        }

        // Analyse criteria 3
        if (!file_exists($results_dir . '/phpcpd/' . $reportFile . '.xml')) {
            $this->logger->info('Analysing phpcpd for ' . $plugin . "...\n");
            exec('php -d memory_limit=1024M vendor/bin/phpcpd \
                    --log-pmd=' . $results_dir . '/phpcpd/' . $reportFile . '.xml \
                    ' . $analyze_dir . ' --exclude=vendor > /dev/null');
        }

        // Because it's only a library, we don't analyse
        // - 6: Couple Architecture Components Loosely
        // - 7: Keep Architecture Components Balanced

        // Analyse criteria 9
        // Using COCOMO model: https://link.springer.com/chapter/10.1007/978-3-319-03629-8_9
        // Algo: 2.4 * (KSLOC^1.05))
        if (!file_exists($results_dir . '/sloccount/' . $reportFile . '.txt')) {
            $this->logger->info('Analysing sloccount for ' . $plugin . "...\n");

            $loc = 0;
            exec('php -d memory_limit=1024M vendor/bin/phploc ' . $analyze_dir . ' \
                    --exclude ' . $analyze_dir . '/vendor | grep "Lines of Code (LOC)"', $loc);

            file_put_contents(
                $results_dir . '/sloccount/' . $reportFile . '.txt',
                trim(substr($loc[0], strlen('Lines of Code (LOC)')+3))
            );
        }

        // Analyse criteria 10
        // Assuming using PHPUnit
        if (!file_exists($results_dir . '/tests/' . $reportFile . '.txt')) {
            $this->logger->info('Analysing tests for ' . $plugin . "...\n");
            exec('grep -r "PHPUnit" ' . $analyze_dir . ' | \
                    grep -v -e "/vendor/" > ' . $results_dir . '/tests/' . $reportFile . '.txt');
        }
    }
}
