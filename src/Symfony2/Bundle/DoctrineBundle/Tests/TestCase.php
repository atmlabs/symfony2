<?php

/*
 * This file is part of the Doctrine Bundle
 *
 * The code was originally distributed inside the Symfony framework.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 * (c) Doctrine Project, Benjamin Eberlei <kontakt@beberlei.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\DoctrineBundle\Tests;

use Doctrine\Common\Annotations\AnnotationReader;
use Symfony2\Bundle\DoctrineBundle\DependencyInjection\DoctrineExtension;
use Symfony2\Component\DependencyInjection\ContainerBuilder;
use Symfony2\Component\DependencyInjection\Definition;
use Symfony2\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony2\Component\DependencyInjection\Compiler\ResolveDefinitionTemplatesPass;

class TestCase extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (!class_exists('Doctrine\\Common\\Version')) {
            $this->markTestSkipped('Doctrine is not available.');
        }
    }

    public function createYamlBundleTestContainer()
    {
        $container = new ContainerBuilder(new ParameterBag(array(
            'kernel.debug' => false,
            'kernel.bundles' => array('YamlBundle' => 'Fixtures\Bundles\YamlBundle\YamlBundle'),
            'kernel.cache_dir' => sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.root_dir' => __DIR__.'/../../../../', // src dir
        )));
        $container->set('annotation_reader', new AnnotationReader());
        $extension = new DoctrineExtension();
        $container->registerExtension($extension);
        $extension->load(array(array(
            'dbal' => array(
                'connections' => array(
                    'default' => array(
                        'driver' => 'pdo_mysql',
                        'charset' => 'UTF8',
                        'platform-service' => 'my.platform',
                    ),
                ),
                'default_connection' => 'default',
                'types' => array(
                    'test' => 'Symfony2\Bundle\DoctrineBundle\Tests\DependencyInjection\TestType',
                ),
            ), 'orm' => array(
                'default_entity_manager' => 'default',
                'entity_managers' => array (
                    'default' => array(
                        'mappings' => array('YamlBundle' => array(
                            'type' => 'yml',
                            'dir' => __DIR__.'/DependencyInjection/Fixtures/Bundles/YamlBundle/Resources/config/doctrine',
                            'prefix' => 'Fixtures\Bundles\YamlBundle\Entity',
                        )),
                    ),
                ),
                'resolve_target_entities' => array(
                    'Symfony2\Component\Security\Core\User\UserInterface' => 'stdClass',
                ),
            ),
        )), $container);

        $container->setDefinition('my.platform', new Definition('Doctrine\DBAL\Platforms\MySqlPlatform'));

        $container->getCompilerPassConfig()->setOptimizationPasses(array(new ResolveDefinitionTemplatesPass()));
        $container->getCompilerPassConfig()->setRemovingPasses(array());
        $container->compile();

        return $container;
    }
}
