<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\DoctrineCacheBundle\DependencyInjection\Definition;

use Symfony2\Component\DependencyInjection\ContainerBuilder;
use Symfony2\Component\DependencyInjection\Definition;

/**
 * Cache Definition.
 *
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
abstract class CacheDefinition
{
    /**
     * @var string
     */
    private $type;

    /**
     * @param string $type
     */
    public function __construct($type)
    {
        $this->type = $type;
    }

    /**
     * @param string                                                    $name
     * @param array                                                     $config
     * @param \Symfony2\Component\DependencyInjection\Definition         $service
     * @param \Symfony2\Component\DependencyInjection\ContainerBuilder   $container
     */
    abstract public function configure($name, array $config, Definition $service, ContainerBuilder $container);
}
