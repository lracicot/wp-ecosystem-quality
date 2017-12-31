<?php

namespace Command;

use DirectoryIterator;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class AnalyseWP extends Command
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
        ->setName('app:analyse:wp')
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

        $start = strtotime('2015-01-01');
        $end = strtotime('2015-12-15');

        do {
            // Checkout the right revision
            $git_cmd = '(cd '.$dataPath.' && git checkout `git rev-list -n 1 --before="'.date('Y-m-d', $start).'" master`)';

            exec($git_cmd);

            $maintainability->analyze($dataPath. '/', $resultPath, date('Y-m', $start));
            $start = strtotime(date('Y-m-d', $start) . " +1 month");
        } while ($start < $end);
    }
}
