<?php

namespace Reporter\Maintainability;

use Reporter\Reporter;

class VisserMetrics extends Reporter
{
    private $xmlParser;

    public function __construct($logger, $xmlParser)
    {
        parent::__construct($logger);
        $this->xmlParser = $xmlParser;
    }

    public function report($analyze_dir)
    {
        $plugin = basename($analyze_dir);
        $results = [];

        // Analyse criteria 1, 2, 4, 6, 11 with phpmd
        // Read phpmd results file

        $phpmd = $this->xmlParser->load(
            dirname($analyze_dir) . '_results/phpmd/' . $plugin . '.xml'
        );

        // Reduce it like a boss
        $results = reduce($phpmd->file, function ($results, $file) {
            $res = reduce($file->violation, function ($results, $violation) {
                switch ($violation['rule']) {
                    case 'ExcessiveClassComplexity':
                        $results['simple_units'] += $violation['endline'] - $violation['beginline'];
                        break;

                    case 'ExcessiveClassLength':
                    case 'ExcessiveMethodLength':
                        $results['short_units'] += $violation['endline'] - $violation['beginline'];
                        break;

                    case 'TooManyPublicMethods':
                        $results['small_interface'] += $violation['endline'] - $violation['beginline'];
                        break;

                    case 'CouplingBetweenObjects':
                        $results['coupling'] += $violation['endline'] - $violation['beginline'];
                        break;

                    default:
                        $results['clean_code'] += $violation['endline'] - $violation['beginline'];
                        break;
                }

                return $results;
            }, [
                'short_units' => 0,
                'simple_units' => 0,
                'small_interface' => 0,
                'coupling' => 0,
                'clean_code' => 0,
                'copy-paste' => 0,
                'loc' => 0,
                'man-year' => 0,
            ]);

            foreach ($res as $key => $value) {
                if (!isset($results[$key])) {
                    $results[$key] = 0;
                }
                $results[$key] += $value;
            }
            return $results;
        }, []);

        // Analyse criteria 3 with phpcpd
        // Read phpcpd results file
        $phpcpd = $this->xmlParser->load(
            dirname($analyze_dir) . '_results/phpcpd/' . $plugin . '.xml'
        );

        $results['copy-paste'] = reduce($phpcpd->duplication, function ($sum, $duplication) {
            return $sum + 1;
        }, 0);

        // Analyse criteria 9 with phploc
        $loc = trim(file_get_contents(
            dirname($analyze_dir) . '_results/sloccount/' . $plugin . '.txt'
        ));

        $results['man-year'] = 2.4 * (($loc/1000) ** 1.05);
        $results['small_codebase'] = $results['man-year'];
        $results['loc'] = $loc;

        // Analyse criteria 10 by looking for PHPUnit classes
        $results['unit_tests'] = false;
        if (file_exists(dirname($analyze_dir) . '_results/tests/' . $plugin . '.txt')) {
            $tests = trim(file_get_contents(
                dirname($analyze_dir) . '_results/tests/' . $plugin . '.txt'
            ));

            $results['unit_tests'] = (empty($tests));
        }

        return $results;
    }
}
