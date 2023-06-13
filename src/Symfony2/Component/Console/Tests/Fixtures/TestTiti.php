<?php

use Symfony2\Component\Console\Command\Command;
use Symfony2\Component\Console\Input\InputInterface;
use Symfony2\Component\Console\Output\OutputInterface;

class TestTiti extends Command
{
    protected function configure()
    {
        $this
            ->setName('test-titi')
            ->setDescription('The test:titi command')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->write('test-titi');
    }
}
