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
 * Adds tagged translation.extractor services to translation extractor.
 */
class TranslationExtractorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('translation.extractor')) {
            return;
        }

        $definition = $container->getDefinition('translation.extractor');

        foreach ($container->findTaggedServiceIds('translation.extractor') as $id => $attributes) {
            if (!isset($attributes[0]['alias'])) {
                throw new \RuntimeException(sprintf('The alias for the tag "translation.extractor" of service "%s" must be set.', $id));
            }

            $definition->addMethodCall('addExtractor', array($attributes[0]['alias'], new Reference($id)));
        }
    }
}
