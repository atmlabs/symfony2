<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\WebProfilerBundle\DependencyInjection;

use Symfony2\Component\Config\Definition\Builder\TreeBuilder;
use Symfony2\Component\Config\Definition\ConfigurationInterface;

/**
 * This class contains the configuration information for the bundle.
 *
 * This information is solely responsible for how the different configuration
 * sections are normalized, and merged.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('web_profiler');

        $rootNode
            ->children()
                ->booleanNode('toolbar')->defaultFalse()->end()
                ->scalarNode('position')
                    ->defaultValue('bottom')
                    ->validate()
                        ->ifNotInArray(array('bottom', 'top'))
                        ->thenInvalid('The CSS position %s is not supported')
                    ->end()
                ->end()
                ->booleanNode('intercept_redirects')->defaultFalse()->end()
                ->scalarNode('excluded_ajax_paths')->defaultValue('^/(app(_[\\w]+)?\\.php/)?_wdt')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
