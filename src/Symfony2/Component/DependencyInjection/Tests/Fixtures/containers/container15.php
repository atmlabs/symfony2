<?php

use Symfony2\Component\DependencyInjection\ContainerBuilder;

$container = new ContainerBuilder();
$container
    ->register('foo', 'FooClass\\Foo')
    ->setDecoratedService('bar', 'bar.woozy')
;

return $container;
