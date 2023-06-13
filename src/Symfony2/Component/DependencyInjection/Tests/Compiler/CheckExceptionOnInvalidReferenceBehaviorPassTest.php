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
use Symfony2\Component\DependencyInjection\Compiler\CheckExceptionOnInvalidReferenceBehaviorPass;
use Symfony2\Component\DependencyInjection\ContainerBuilder;
use Symfony2\Component\DependencyInjection\Definition;
use Symfony2\Component\DependencyInjection\Reference;

class CheckExceptionOnInvalidReferenceBehaviorPassTest extends TestCase
{
    public function testProcess()
    {
        $container = new ContainerBuilder();

        $container
            ->register('a', '\stdClass')
            ->addArgument(new Reference('b'))
        ;
        $container->register('b', '\stdClass');

        $this->process($container);

        $this->addToAssertionCount(1);
    }

    /**
     * @expectedException \Symfony2\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function testProcessThrowsExceptionOnInvalidReference()
    {
        $container = new ContainerBuilder();

        $container
            ->register('a', '\stdClass')
            ->addArgument(new Reference('b'))
        ;

        $this->process($container);
    }

    /**
     * @expectedException \Symfony2\Component\DependencyInjection\Exception\ServiceNotFoundException
     */
    public function testProcessThrowsExceptionOnInvalidReferenceFromInlinedDefinition()
    {
        $container = new ContainerBuilder();

        $def = new Definition();
        $def->addArgument(new Reference('b'));

        $container
            ->register('a', '\stdClass')
            ->addArgument($def)
        ;

        $this->process($container);
    }

    private function process(ContainerBuilder $container)
    {
        $pass = new CheckExceptionOnInvalidReferenceBehaviorPass();
        $pass->process($container);
    }
}
