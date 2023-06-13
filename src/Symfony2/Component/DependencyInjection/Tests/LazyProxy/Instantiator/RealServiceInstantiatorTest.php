<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\DependencyInjection\Tests\LazyProxy\Instantiator;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\DependencyInjection\Definition;
use Symfony2\Component\DependencyInjection\LazyProxy\Instantiator\RealServiceInstantiator;

/**
 * Tests for {@see \Symfony2\Component\DependencyInjection\Instantiator\RealServiceInstantiator}.
 *
 * @author Marco Pivetta <ocramius@gmail.com>
 */
class RealServiceInstantiatorTest extends TestCase
{
    public function testInstantiateProxy()
    {
        $instantiator = new RealServiceInstantiator();
        $instance = new \stdClass();
        $container = $this->getMockBuilder('Symfony2\Component\DependencyInjection\ContainerInterface')->getMock();
        $callback = function () use ($instance) {
            return $instance;
        };

        $this->assertSame($instance, $instantiator->instantiateProxy($container, new Definition(), 'foo', $callback));
    }
}
