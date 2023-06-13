<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\FrameworkBundle\Tests\Functional\Bundle\TestBundle\DependencyInjection;

use Symfony2\Component\Config\Definition\Builder\TreeBuilder;
use Symfony2\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    private $customConfig;

    public function __construct($customConfig = null)
    {
        $this->customConfig = $customConfig;
    }

    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('test');

        if ($this->customConfig) {
            $this->customConfig->addConfiguration($rootNode);
        }

        return $treeBuilder;
    }
}
