<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\DependencyInjection\Tests\Compiler;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\DependencyInjection\Compiler\CheckDefinitionValidityPass;
use Symfony2\Component\DependencyInjection\ContainerBuilder;
use Symfony2\Component\DependencyInjection\ContainerInterface;

class CheckDefinitionValidityPassTest extends TestCase
{
    /**
     * @expectedException \Symfony2\Component\DependencyInjection\Exception\RuntimeException
     */
    public function testProcessDetectsSyntheticNonPublicDefinitions()
    {
        $container = new ContainerBuilder();
        $container->register('a')->setSynthetic(true)->setPublic(false);

        $this->process($container);
    }

    /**
     * @expectedException \Symfony2\Component\DependencyInjection\Exception\RuntimeException
     * @group legacy
     */
    public function testProcessDetectsSyntheticPrototypeDefinitions()
    {
        $container = new ContainerBuilder();
        $container->register('a')->setSynthetic(true)->setScope(ContainerInterface::SCOPE_PROTOTYPE);

        $this->process($container);
    }

    /**
     * @expectedException \Symfony2\Component\DependencyInjection\Exception\RuntimeException
     * @group legacy
     */
    public function testProcessDetectsSharedPrototypeDefinitions()
    {
        $container = new ContainerBuilder();
        $container->register('a')->setShared(true)->setScope(ContainerInterface::SCOPE_PROTOTYPE);

        $this->process($container);
    }

    /**
     * @expectedException \Symfony2\Component\DependencyInjection\Exception\RuntimeException
     */
    public function testProcessDetectsNonSyntheticNonAbstractDefinitionWithoutClass()
    {
        $container = new ContainerBuilder();
        $container->register('a')->setSynthetic(false)->setAbstract(false);

        $this->process($container);
    }

    /**
     * @expectedException \Symfony2\Component\DependencyInjection\Exception\RuntimeException
     * @group legacy
     */
    public function testLegacyProcessDetectsBothFactorySyntaxesUsed()
    {
        $container = new ContainerBuilder();
        $container->register('a')->setFactory(array('a', 'b'))->setFactoryClass('a');

        $this->process($container);
    }

    public function testProcess()
    {
        $container = new ContainerBuilder();
        $container->register('a', 'class');
        $container->register('b', 'class')->setSynthetic(true)->setPublic(true);
        $container->register('c', 'class')->setAbstract(true);
        $container->register('d', 'class')->setSynthetic(true);

        $this->process($container);

        $this->addToAssertionCount(1);
    }

    public function testValidTags()
    {
        $container = new ContainerBuilder();
        $container->register('a', 'class')->addTag('foo', array('bar' => 'baz'));
        $container->register('b', 'class')->addTag('foo', array('bar' => null));
        $container->register('c', 'class')->addTag('foo', array('bar' => 1));
        $container->register('d', 'class')->addTag('foo', array('bar' => 1.1));

        $this->process($container);

        $this->addToAssertionCount(1);
    }

    /**
     * @expectedException \Symfony2\Component\DependencyInjection\Exception\RuntimeException
     */
    public function testInvalidTags()
    {
        $container = new ContainerBuilder();
        $container->register('a', 'class')->addTag('foo', array('bar' => array('baz' => 'baz')));

        $this->process($container);
    }

    protected function process(ContainerBuilder $container)
    {
        $pass = new CheckDefinitionValidityPass();
        $pass->process($container);
    }
}
