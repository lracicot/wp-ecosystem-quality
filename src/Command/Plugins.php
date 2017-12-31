<?php

namespace Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Plugins extends Command
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
        ->setName('app:plugins:fetch')
        ->setDescription('')
        ->setHelp('');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $api = $this->container['wp_api'];
        $response = $api->popularPlugins(1, 100)->wait();
        $results = unserialize($response->getBody()->getContents());

        foreach ($results->plugins as $plugin) {
            echo 'Downloading ' . $plugin->name;
            exec('(cd data/plugins && wget -qO- ' . $plugin->download_link . ' | bsdtar -xvf-)');
        }
    }
}
