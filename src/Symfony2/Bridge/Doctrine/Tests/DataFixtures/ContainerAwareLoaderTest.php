<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bridge\Doctrine\Tests\DataFixtures;

use PHPUnit\Framework\TestCase;
use Symfony2\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Symfony2\Bridge\Doctrine\Tests\Fixtures\ContainerAwareFixture;

class ContainerAwareLoaderTest extends TestCase
{
    public function testShouldSetContainerOnContainerAwareFixture()
    {
        $container = $this->getMockBuilder('Symfony2\Component\DependencyInjection\ContainerInterface')->getMock();
        $loader = new ContainerAwareLoader($container);
        $fixture = new ContainerAwareFixture();

        $loader->addFixture($fixture);

        $this->assertSame($container, $fixture->container);
    }
}
