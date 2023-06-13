<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\HttpKernel\Tests\Fixtures\ExtensionPresentBundle\DependencyInjection;

use Symfony2\Component\DependencyInjection\ContainerBuilder;
use Symfony2\Component\DependencyInjection\Extension\Extension;

class ExtensionPresentExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
    }
}
