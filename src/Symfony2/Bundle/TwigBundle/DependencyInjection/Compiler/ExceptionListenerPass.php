<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\TwigBundle\DependencyInjection\Compiler;

use Symfony2\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony2\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers the Twig exception listener if Twig is registered as a templating engine.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ExceptionListenerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (false === $container->hasDefinition('twig')) {
            return;
        }

        // register the exception controller only if Twig is enabled and required dependencies do exist
        if (!class_exists('Symfony2\Component\Debug\Exception\FlattenException') || !interface_exists('Symfony2\Component\EventDispatcher\EventSubscriberInterface')) {
            $container->removeDefinition('twig.exception_listener');
        } elseif ($container->hasParameter('templating.engines')) {
            $engines = $container->getParameter('templating.engines');
            if (!\in_array('twig', $engines)) {
                $container->removeDefinition('twig.exception_listener');
            }
        }
    }
}
