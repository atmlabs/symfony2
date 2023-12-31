<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\DebugBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony2\Bundle\DebugBundle\DependencyInjection\DebugExtension;
use Symfony2\Component\DependencyInjection\ContainerBuilder;
use Symfony2\Component\DependencyInjection\ParameterBag\ParameterBag;

class DebugExtensionTest extends TestCase
{
    public function testLoadWithoutConfiguration()
    {
        $container = $this->createContainer();
        $container->registerExtension(new DebugExtension());
        $container->loadFromExtension('debug', array());
        $this->compileContainer($container);

        $expectedTags = array(
            array(
                'id' => 'dump',
                'template' => '@Debug/Profiler/dump.html.twig',
                'priority' => 240,
            ),
        );

        $this->assertSame($expectedTags, $container->getDefinition('data_collector.dump')->getTag('data_collector'));
    }

    private function createContainer()
    {
        $container = new ContainerBuilder(new ParameterBag(array(
            'kernel.cache_dir' => __DIR__,
            'kernel.root_dir' => __DIR__.'/Fixtures',
            'kernel.charset' => 'UTF-8',
            'kernel.debug' => true,
            'kernel.bundles' => array('DebugBundle' => 'Symfony2\\Bundle\\DebugBundle\\DebugBundle'),
        )));

        return $container;
    }

    private function compileContainer(ContainerBuilder $container)
    {
        $container->getCompilerPassConfig()->setOptimizationPasses(array());
        $container->getCompilerPassConfig()->setRemovingPasses(array());
        $container->compile();
    }
}
