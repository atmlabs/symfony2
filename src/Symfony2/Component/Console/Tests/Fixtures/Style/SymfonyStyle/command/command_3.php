<?php

use Symfony2\Component\Console\Input\InputInterface;
use Symfony2\Component\Console\Output\OutputInterface;
use Symfony2\Component\Console\Tests\Style\SymfonyStyleWithForcedLineLength;

//Ensure has single blank line between two titles
return function (InputInterface $input, OutputInterface $output) {
    $output = new SymfonyStyleWithForcedLineLength($input, $output);
    $output->title('First title');
    $output->title('Second title');
};
