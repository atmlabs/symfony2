<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\FrameworkBundle\DependencyInjection\Compiler;

use Symfony2\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony2\Component\DependencyInjection\ContainerBuilder;

class TemplatingPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('templating')) {
            return;
        }

        if ($container->hasDefinition('templating.engine.php')) {
            $helpers = array();
            foreach ($container->findTaggedServiceIds('templating.helper') as $id => $attributes) {
                if (isset($attributes[0]['alias'])) {
                    $helpers[$attributes[0]['alias']] = $id;
                }
            }

            if (\count($helpers) > 0) {
                $definition = $container->getDefinition('templating.engine.php');
                $definition->addMethodCall('setHelpers', array($helpers));
            }
        }
    }
}
