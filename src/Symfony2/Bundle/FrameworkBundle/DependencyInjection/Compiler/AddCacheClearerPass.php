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
 * Registers the cache clearers.
 *
 * @author Dustin Dobervich <ddobervich@gmail.com>
 */
class AddCacheClearerPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('cache_clearer')) {
            return;
        }

        $clearers = array();
        foreach ($container->findTaggedServiceIds('kernel.cache_clearer') as $id => $attributes) {
            $clearers[] = new Reference($id);
        }

        $container->getDefinition('cache_clearer')->replaceArgument(0, $clearers);
    }
}
