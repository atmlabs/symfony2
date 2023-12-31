<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\SecurityBundle\DependencyInjection\Security\Factory;

use Symfony2\Component\Config\Definition\Builder\NodeDefinition;
use Symfony2\Component\DependencyInjection\ContainerBuilder;
use Symfony2\Component\DependencyInjection\DefinitionDecorator;
use Symfony2\Component\DependencyInjection\Reference;

/**
 * X509Factory creates services for X509 certificate authentication.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class X509Factory implements SecurityFactoryInterface
{
    public function create(ContainerBuilder $container, $id, $config, $userProvider, $defaultEntryPoint)
    {
        $providerId = 'security.authentication.provider.pre_authenticated.'.$id;
        $container
            ->setDefinition($providerId, new DefinitionDecorator('security.authentication.provider.pre_authenticated'))
            ->replaceArgument(0, new Reference($userProvider))
            ->replaceArgument(1, new Reference('security.user_checker.'.$id))
            ->addArgument($id)
        ;

        // listener
        $listenerId = 'security.authentication.listener.x509.'.$id;
        $listener = $container->setDefinition($listenerId, new DefinitionDecorator('security.authentication.listener.x509'));
        $listener->replaceArgument(2, $id);
        $listener->replaceArgument(3, $config['user']);
        $listener->replaceArgument(4, $config['credentials']);
        $listener->addMethodCall('setSessionAuthenticationStrategy', array(new Reference('security.authentication.session_strategy.'.$id)));

        return array($providerId, $listenerId, $defaultEntryPoint);
    }

    public function getPosition()
    {
        return 'pre_auth';
    }

    public function getKey()
    {
        return 'x509';
    }

    public function addConfiguration(NodeDefinition $node)
    {
        $node
            ->children()
                ->scalarNode('provider')->end()
                ->scalarNode('user')->defaultValue('SSL_CLIENT_S_DN_Email')->end()
                ->scalarNode('credentials')->defaultValue('SSL_CLIENT_S_DN')->end()
            ->end()
        ;
    }
}
