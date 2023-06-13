<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\MonologBundle\Tests\DependencyInjection;

use Symfony2\Component\Config\FileLocator;
use Symfony2\Component\DependencyInjection\ContainerBuilder;
use Symfony2\Component\DependencyInjection\Loader\YamlFileLoader;

class YamlMonologExtensionTest extends FixtureMonologExtensionTest
{
    protected function loadFixture(ContainerBuilder $container, $fixture)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/Fixtures/yml'));
        $loader->load($fixture.'.yml');
    }
}
