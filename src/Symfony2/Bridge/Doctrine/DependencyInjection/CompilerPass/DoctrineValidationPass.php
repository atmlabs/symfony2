<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bridge\Doctrine\DependencyInjection\CompilerPass;

use Symfony2\Component\Config\Resource\FileResource;
use Symfony2\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony2\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers additional validators.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class DoctrineValidationPass implements CompilerPassInterface
{
    private $managerType;

    /**
     * @param string $managerType
     */
    public function __construct($managerType)
    {
        $this->managerType = $managerType;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $this->updateValidatorMappingFiles($container, 'xml', 'xml');
        $this->updateValidatorMappingFiles($container, 'yaml', 'yml');
    }

    /**
     * Gets the validation mapping files for the format and extends them with
     * files matching a doctrine search pattern (Resources/config/validation.orm.xml).
     *
     * @param ContainerBuilder $container
     * @param string           $mapping
     * @param string           $extension
     */
    private function updateValidatorMappingFiles(ContainerBuilder $container, $mapping, $extension)
    {
        if (!$container->hasParameter('validator.mapping.loader.'.$mapping.'_files_loader.mapping_files')) {
            return;
        }

        $files = $container->getParameter('validator.mapping.loader.'.$mapping.'_files_loader.mapping_files');
        $validationPath = 'Resources/config/validation.'.$this->managerType.'.'.$extension;

        foreach ($container->getParameter('kernel.bundles') as $bundle) {
            $reflection = new \ReflectionClass($bundle);
            if (is_file($file = \dirname($reflection->getFileName()).'/'.$validationPath)) {
                $files[] = $file;
                $container->addResource(new FileResource($file));
            }
        }

        $container->setParameter('validator.mapping.loader.'.$mapping.'_files_loader.mapping_files', $files);
    }
}
