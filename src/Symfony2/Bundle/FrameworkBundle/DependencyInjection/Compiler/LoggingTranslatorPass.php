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
use Symfony2\Component\DependencyInjection\Reference;

/**
 * @author Abdellatif Ait boudad <a.aitboudad@gmail.com>
 */
class LoggingTranslatorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasAlias('logger') || !$container->hasAlias('translator')) {
            return;
        }

        if ($container->hasParameter('translator.logging') && $container->getParameter('translator.logging')) {
            $translatorAlias = $container->getAlias('translator');
            $definition = $container->getDefinition((string) $translatorAlias);
            $class = $container->getParameterBag()->resolveValue($definition->getClass());

            if (is_subclass_of($class, 'Symfony2\Component\Translation\TranslatorInterface') && is_subclass_of($class, 'Symfony2\Component\Translation\TranslatorBagInterface')) {
                $container->getDefinition('translator.logging')->setDecoratedService('translator');
                $container->getDefinition('translation.warmer')->replaceArgument(0, new Reference('translator.logging.inner'));
            }
        }
    }
}
