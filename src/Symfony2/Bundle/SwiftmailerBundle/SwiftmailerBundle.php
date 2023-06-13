<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\SwiftmailerBundle;

use Symfony2\Component\HttpKernel\Bundle\Bundle;
use Symfony2\Component\DependencyInjection\ContainerBuilder;
use Symfony2\Bundle\SwiftmailerBundle\DependencyInjection\Compiler\RegisterPluginsPass;

/**
 * Bundle.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SwiftmailerBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new RegisterPluginsPass());
    }
}
