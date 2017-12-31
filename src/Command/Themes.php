<?php

namespace Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Themes extends Command
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
        ->setName('app:themes:fetch')
        ->setDescription('')
        ->setHelp('');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $api = $this->container['wp_api'];
        $response = $api->popularThemes(1, 100)->wait();
        $results = unserialize($response->getBody()->getContents());

        foreach ($results->themes as $theme) {
            $latest = end($theme->versions);
            echo 'Downloading ' . $theme->name;
            exec('(cd data/themes && wget -qO- ' . $latest . ' | bsdtar -xvf-)');
        }
    }
}
