<?php

use Symfony2\Component\Console\Command\Command;
use Symfony2\Component\Console\Input\InputInterface;
use Symfony2\Component\Console\Output\OutputInterface;

class FoobarCommand extends Command
{
    public $input;
    public $output;

    protected function configure()
    {
        $this
            ->setName('foobar:foo')
            ->setDescription('The foobar:foo command')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }
}
