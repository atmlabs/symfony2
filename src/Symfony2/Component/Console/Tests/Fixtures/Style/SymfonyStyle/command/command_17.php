<?php

use Symfony2\Component\Console\Input\InputInterface;
use Symfony2\Component\Console\Output\OutputInterface;
use Symfony2\Component\Console\Tests\Style\SymfonyStyleWithForcedLineLength;

//Ensure symfony style helper methods handle trailing backslashes properly when decorating user texts
return function (InputInterface $input, OutputInterface $output) {
    $output = new SymfonyStyleWithForcedLineLength($input, $output);

    $output->title('Title ending with \\');
    $output->section('Section ending with \\');
};
