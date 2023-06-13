<?php

namespace Symfony2\Bundle\SecurityBundle\Tests\DependencyInjection\Fixtures\UserProvider;

use Symfony2\Bundle\SecurityBundle\DependencyInjection\Security\UserProvider\UserProviderFactoryInterface;
use Symfony2\Component\Config\Definition\Builder\NodeDefinition;
use Symfony2\Component\DependencyInjection\ContainerBuilder;

class DummyProvider implements UserProviderFactoryInterface
{
    public function create(ContainerBuilder $container, $id, $config)
    {
    }

    public function getKey()
    {
        return 'foo';
    }

    public function addConfiguration(NodeDefinition $node)
    {
    }
}
