<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\SecurityBundle\Tests\DependencyInjection;

use Symfony2\Component\Config\FileLocator;
use Symfony2\Component\DependencyInjection\ContainerBuilder;
use Symfony2\Component\DependencyInjection\Loader\XmlFileLoader;

class XmlCompleteConfigurationTest extends CompleteConfigurationTest
{
    protected function getLoader(ContainerBuilder $container)
    {
        return new XmlFileLoader($container, new FileLocator(__DIR__.'/Fixtures/xml'));
    }

    protected function getFileExtension()
    {
        return 'xml';
    }
}
