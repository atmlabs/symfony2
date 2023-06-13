<?php

use Symfony2\Component\DependencyInjection\ContainerBuilder;
use Symfony2\Component\DependencyInjection\Scope;

$container = new ContainerBuilder();
$container->addScope(new Scope('request'));
$container->
    register('foo', 'FooClass')->
    setScope('request')
;
$container->compile();

return $container;
