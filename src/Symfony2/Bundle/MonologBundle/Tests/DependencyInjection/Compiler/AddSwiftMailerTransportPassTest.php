<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\MonologBundle\Tests\DependencyInjection\Compiler;

use Symfony2\Bundle\MonologBundle\DependencyInjection\Compiler\AddSwiftMailerTransportPass;
use Symfony2\Component\DependencyInjection\Reference;

/**
 * @author Christian Flothmann <christian.flothmann@xabbuh.de>
 */
class AddSwiftMailerTransportPassTest extends \PHPUnit_Framework_TestCase
{
    private $compilerPass;

    private $container;

    private $definition;

    protected function setUp()
    {
        $this->compilerPass = new AddSwiftMailerTransportPass();
        $this->definition = $this->getMock('\Symfony2\Component\DependencyInjection\Definition');
        $this->definition->expects($this->any())
            ->method('getArgument')
            ->with(0)
            ->will($this->returnValue(new Reference('swiftmailer')));
        $this->container = $this->getMock('\Symfony2\Component\DependencyInjection\ContainerBuilder', array('getParameter', 'getDefinition', 'hasDefinition', 'addMethodCall'));
        $this->container->expects($this->any())
            ->method('getParameter')
            ->with('monolog.swift_mailer.handlers')
            ->will($this->returnValue(array('foo')));
        $this->container->expects($this->any())
            ->method('getDefinition')
            ->with('foo')
            ->will($this->returnValue($this->definition));
    }

    public function testWithRealTransport()
    {
        $this->container
            ->expects($this->any())
            ->method('hasDefinition')
            ->with('swiftmailer.transport.real')
            ->will($this->returnValue(true));
        $this->definition
            ->expects($this->once())
            ->method('addMethodCall')
            ->with(
                'setTransport',
                $this->equalTo(array(new Reference('swiftmailer.transport.real')))
            );

        $this->compilerPass->process($this->container);
    }

    public function testWithoutRealTransport()
    {
        $this->container
            ->expects($this->any())
            ->method('hasDefinition')
            ->will($this->returnValueMap(
                array(
                    array('swiftmailer.transport.real', false),
                    array('swiftmailer.transport', true),
                )
            ));
        $this->definition
            ->expects($this->once())
            ->method('addMethodCall')
            ->with(
                'setTransport',
                $this->equalTo(array(new Reference('swiftmailer.transport')))
            );

        $this->compilerPass->process($this->container);
    }
}
