<?php

namespace Sensio\Bundle\DistributionBundle\DependencyInjection;

use Symfony2\Component\DependencyInjection\ContainerBuilder;
use Symfony2\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony2\Component\HttpKernel\DependencyInjection\Extension;
use Symfony2\Component\Config\FileLocator;

/**
 * SensioDistributionExtension.
 *
 * @author Marc Weistroff <marc.weistroff@sensio.com>
 */
class SensioDistributionExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));

        $loader->load('webconfigurator.xml');
        $loader->load('security.xml');
    }

    public function getNamespace()
    {
        return 'http://symfony.com/schema/dic/symfony/sensiodistribution';
    }

    public function getAlias()
    {
        return 'sensio_distribution';
    }
}
