<?php

use Symfony2\Component\DependencyInjection\ContainerBuilder;

$container = new ContainerBuilder();
$container->
    register('foo', 'FooClass\\Foo')->
    addArgument('foo<>&bar')->
    addTag('foo"bar\\bar', array('foo' => 'foo"barřž€'))
;

return $container;
