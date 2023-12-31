<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\DependencyInjection\Tests;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\DependencyInjection\ContainerBuilder;
use Symfony2\Component\DependencyInjection\Reference;

/**
 * @group legacy
 */
class LegacyContainerBuilderTest extends TestCase
{
    public function testCreateServiceFactoryMethod()
    {
        $builder = new ContainerBuilder();
        $builder->register('bar', 'stdClass');
        $builder->register('foo1', 'Bar\FooClass')->setFactoryClass('Bar\FooClass')->setFactoryMethod('getInstance')->addArgument(array('foo' => '%value%', '%value%' => 'foo', new Reference('bar')));
        $builder->setParameter('value', 'bar');
        $this->assertTrue($builder->get('foo1')->called, '->createService() calls the factory method to create the service instance');
        $this->assertEquals(array('foo' => 'bar', 'bar' => 'foo', $builder->get('bar')), $builder->get('foo1')->arguments, '->createService() passes the arguments to the factory method');
    }

    public function testCreateServiceFactoryService()
    {
        $builder = new ContainerBuilder();
        $builder->register('baz_service')->setFactoryService('baz_factory')->setFactoryMethod('getInstance');
        $builder->register('baz_factory', 'BazClass');

        $this->assertInstanceOf('BazClass', $builder->get('baz_service'));
    }
}
