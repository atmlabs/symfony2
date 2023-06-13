<?php

use Symfony2\Component\Console\Command\Command;
use Symfony2\Component\Console\Input\InputInterface;
use Symfony2\Component\Console\Output\OutputInterface;

class FooSubnamespaced2Command extends Command
{
    public $input;
    public $output;

    protected function configure()
    {
        $this
            ->setName('foo:go:bret')
            ->setDescription('The foo:bar:go command')
            ->setAliases(array('foobargo'))
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }
}
