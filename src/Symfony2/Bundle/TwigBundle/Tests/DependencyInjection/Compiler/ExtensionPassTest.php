<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\TwigBundle\Tests\DependencyInjection\Compiler;

use PHPUnit\Framework\TestCase;
use Symfony2\Bundle\TwigBundle\DependencyInjection\Compiler\ExtensionPass;
use Symfony2\Component\DependencyInjection\ContainerBuilder;
use Symfony2\Component\DependencyInjection\Definition;

class ExtensionPassTest extends TestCase
{
    public function testProcessDoesNotDropExistingFileLoaderMethodCalls()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $container->register('twig.app_variable', '\Symfony2\Bridge\Twig\AppVariable');
        $container->register('templating', '\Symfony2\Bundle\TwigBundle\TwigEngine');

        $nativeTwigLoader = new Definition('\Twig\Loader\FilesystemLoader');
        $nativeTwigLoader->addMethodCall('addPath', array());
        $container->setDefinition('twig.loader.native_filesystem', $nativeTwigLoader);

        $filesystemLoader = new Definition('\Symfony2\Bundle\TwigBundle\Loader\FilesystemLoader');
        $filesystemLoader->addMethodCall('addPath', array());
        $container->setDefinition('twig.loader.filesystem', $filesystemLoader);

        $extensionPass = new ExtensionPass();
        $extensionPass->process($container);

        $this->assertCount(2, $filesystemLoader->getMethodCalls());
    }
}
