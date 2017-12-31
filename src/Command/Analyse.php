<?php

namespace Command;

use DirectoryIterator;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class Analyse extends Command
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
        ->setName('app:analyse')
        ->setDescription('')
        ->setHelp('')

        ->addArgument('path', InputArgument::REQUIRED, 'The paths of the projects to analyze.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dataPath = BASEDIR.'/'.$input->getArgument('path');
        $resultPath = BASEDIR.'/'.$input->getArgument('path').'_results';

        $this->container->extend('progress_logger', function ($logger, $c) use ($output) {
            $logger->pushHandler(new StreamHandler($output->getStream(), Logger::INFO));
            return $logger;
        });

        $maintainability = $this->container['maintainability_analyzer'];
        $wordpresscoupling = $this->container['wordpresscoupling_analyzer'];
        $namespacepollution = $this->container['namespacepollution_analyzer'];

        // Validity: maybe some third-party?
        foreach (new DirectoryIterator($dataPath) as $fileInfo) {
            if ($fileInfo->isDot() || !$fileInfo->isDir()) {
                continue;
            }

            $plugin = $fileInfo->getBasename();

            // Maintainability
            $maintainability->analyze($dataPath. '/' . $plugin, $resultPath);

            // Framework coupling
            $results = $wordpresscoupling->analyze(
                $dataPath. '/' . $plugin
            );

            if (!file_exists($resultPath . '/wp_coupling/')) {
                mkdir($resultPath . '/wp_coupling/', 0777, true);
            }

            file_put_contents(
                $resultPath . '/wp_coupling/' . $plugin . '.json',
                json_encode($results)
            );

            // Namespace pollution
            $results = $namespacepollution->analyze(
                $dataPath. '/' . $plugin
            );

            if (!file_exists($resultPath . '/namespace_pollution/')) {
                mkdir($resultPath . '/namespace_pollution/', 0777, true);
            }

            file_put_contents(
                $resultPath . '/namespace_pollution/' . $plugin,
                $results
            );
        }
    }
}
