<?php

namespace Command;

use DirectoryIterator;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class ReportWP extends Command
{
    private $container;

    public function __construct($container)
    {
        parent::__construct();
        $this->container = $container;
    }

    protected function configure()
    {
        $this
        ->setName('app:report:wp')
        ->setDescription('')
        ->setHelp('')

        ->addArgument('path', InputArgument::REQUIRED, 'The paths of the projects to analyze.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dataPath = BASEDIR.'/'.$input->getArgument('path');
        $resultsPath = BASEDIR.'/'.$input->getArgument('path').'_results';
        $reportPath = BASEDIR.'/'.$input->getArgument('path').'_report';
        $data = [];
        $report = [];

        $this->container->extend('progress_logger', function ($logger, $c) use ($output) {
            $logger->pushHandler(new StreamHandler($output->getStream(), Logger::INFO));
            return $logger;
        });

        $maintainability = $this->container['maintainability_reporter'];

        foreach (new DirectoryIterator($resultsPath.'/phpmd') as $fileInfo) {
            if ($fileInfo->isDot() || $fileInfo->isDir()) {
                continue;
            }

            $snapshot = substr($fileInfo->getBasename(), 0, -4);

            $data['maintainability'][$snapshot] = $maintainability->report($dataPath. '/' . $snapshot);
        }

        $report['rework_effort'] = map($data['maintainability'], function ($snapshotData) {
            $rework_fractions = max([
              $snapshotData['short_units'],
              $snapshotData['simple_units'],
              $snapshotData['small_interface'],
              $snapshotData['coupling'],
              //$snapshotData['clean_code'],
              $snapshotData['copy-paste'],
              $snapshotData['small_codebase']
            ]) / $snapshotData['loc'];

            $rework_value = $snapshotData['loc'] * 0.00136;
            $refactoring_adjustment = 0.10;

            return $rework_fractions * $rework_value * $refactoring_adjustment;
        });

        ksort($report['rework_effort']);
        $csv = implode("\n", map($report['rework_effort'], function ($debt, $snapshot) {
            return '"' . $snapshot . '",' . $debt;
        }));

        file_put_contents(
            $reportPath.'/rework_effort.csv',
            "Date, Debt\n" . $csv
        );
    }
}
