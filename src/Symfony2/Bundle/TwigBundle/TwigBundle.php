<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\TwigBundle;

use Symfony2\Bundle\TwigBundle\DependencyInjection\Compiler\ExceptionListenerPass;
use Symfony2\Bundle\TwigBundle\DependencyInjection\Compiler\ExtensionPass;
use Symfony2\Bundle\TwigBundle\DependencyInjection\Compiler\TwigEnvironmentPass;
use Symfony2\Bundle\TwigBundle\DependencyInjection\Compiler\TwigLoaderPass;
use Symfony2\Component\DependencyInjection\ContainerBuilder;
use Symfony2\Component\HttpKernel\Bundle\Bundle;

/**
 * Bundle.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class TwigBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ExtensionPass());
        $container->addCompilerPass(new TwigEnvironmentPass());
        $container->addCompilerPass(new TwigLoaderPass());
        $container->addCompilerPass(new ExceptionListenerPass());
    }
}
