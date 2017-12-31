<?php

namespace Command;

use DirectoryIterator;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Report extends Command
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
        ->setName('app:report')
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
        $wordpresscoupling = $this->container['wordpresscoupling_reporter'];
        $phpversion = $this->container['phpversion_reporter'];
        $namespacepollution = $this->container['namespacepollution_reporter'];

        foreach (new DirectoryIterator($dataPath) as $fileInfo) {
            if ($fileInfo->isDot() || !$fileInfo->isDir()) {
                continue;
            }

            $plugin = $fileInfo->getBasename();

            $loc = trim(file_get_contents(
                dirname($fileInfo->getRealPath()) . '_results/sloccount/' . $plugin . '.txt'
            ));

            $data['maintainability'][$plugin] = $maintainability->report($fileInfo->getRealPath());
            $data['wordpresscoupling'][$plugin] = $wordpresscoupling->report($fileInfo->getRealPath());
            $data['namespacepollution'][$plugin] = $namespacepollution->report($fileInfo->getRealPath());
        }

        $report['maintainability'] = map($data['maintainability'], function ($pluginData, $plugin) {
            $metrics = ['short_units', 'simple_units', 'small_interface', 'coupling', 'clean_code', 'copy-paste'];
            $filtered = array_filter(
                $pluginData,
                function ($key) use ($metrics) {
                    return in_array($key, $metrics);
                },
                ARRAY_FILTER_USE_KEY
            );

            $loc = $pluginData['loc'];

            return max(map($filtered, function ($value, $metric) use ($loc) {
                return $value / $loc;
            }));
        });

        $report['maintainability'] = implode("\n", map($report['maintainability'], function ($re, $plugin) {
            return '"' . $plugin . '",' . $re;
        }));

        $report['wordpresscoupling'] = implode(
            "\n",
            map($data['wordpresscoupling'], function ($coupling, $plugin) use ($data) {
                return '"' . $plugin . '",' . $coupling;
            })
        );
        $report['namespacepollution'] = implode(
            "\n",
            map($data['namespacepollution'], function ($pollution, $plugin) {
                return '"' . $plugin . '",' . $pollution;
            })
        );

        file_put_contents(
            $reportPath.'/maintainability.csv',
            "Plugin, Re\n" . $report['maintainability']
        );
        file_put_contents(
            $reportPath . '/wordpresscoupling.csv',
            "Plugin, Coupling\n" . $report['wordpresscoupling']
        );
        file_put_contents(
            $reportPath . '/namespacepollution.csv',
            "Plugin, Pollution\n" . $report['namespacepollution']
        );
    }
}
