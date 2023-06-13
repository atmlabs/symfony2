<?php

use Symfony2\Component\DependencyInjection\ContainerBuilder;

$container = new ContainerBuilder();
$container
    ->register('foo', '%foo.class%')
;

return $container;
