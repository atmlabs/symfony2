<?php

use Symfony2\Component\Console\Command\Command;
use Symfony2\Component\Console\Input\InputInterface;
use Symfony2\Component\Console\Output\OutputInterface;

class Foo2Command extends Command
{
    protected function configure()
    {
        $this
            ->setName('foo1:bar')
            ->setDescription('The foo1:bar command')
            ->setAliases(array('afoobar2'))
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    }
}
