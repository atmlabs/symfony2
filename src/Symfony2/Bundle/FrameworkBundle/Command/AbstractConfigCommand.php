<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\FrameworkBundle\Command;

use Symfony2\Component\Config\Definition\ConfigurationInterface;
use Symfony2\Component\Console\Helper\Table;
use Symfony2\Component\Console\Style\StyleInterface;
use Symfony2\Component\DependencyInjection\Extension\ExtensionInterface;

/**
 * A console command for dumping available configuration reference.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 * @author Wouter J <waldio.webdesign@gmail.com>
 * @author Grégoire Pineau <lyrixx@lyrixx.info>
 */
abstract class AbstractConfigCommand extends ContainerDebugCommand
{
    protected function listBundles($output)
    {
        $headers = array('Bundle name', 'Extension alias');
        $rows = array();

        $bundles = $this->getContainer()->get('kernel')->getBundles();
        usort($bundles, function ($bundleA, $bundleB) {
            return strcmp($bundleA->getName(), $bundleB->getName());
        });

        foreach ($bundles as $bundle) {
            $extension = $bundle->getContainerExtension();
            $rows[] = array($bundle->getName(), $extension ? $extension->getAlias() : '');
        }

        if ($output instanceof StyleInterface) {
            $output->table($headers, $rows);
        } else {
            $output->writeln('Available registered bundles with their extension alias if available:');
            $table = new Table($output);
            $table->setHeaders($headers)->setRows($rows)->render();
        }
    }

    protected function findExtension($name)
    {
        $bundles = $this->initializeBundles();
        foreach ($bundles as $bundle) {
            if ($name === $bundle->getName()) {
                if (!$bundle->getContainerExtension()) {
                    throw new \LogicException(sprintf('Bundle "%s" does not have a container extension.', $name));
                }

                return $bundle->getContainerExtension();
            }

            $extension = $bundle->getContainerExtension();
            if ($extension && $name === $extension->getAlias()) {
                return $extension;
            }
        }

        if ('Bundle' !== substr($name, -6)) {
            $message = sprintf('No extensions with configuration available for "%s"', $name);
        } else {
            $message = sprintf('No extension with alias "%s" is enabled', $name);
        }

        throw new \LogicException($message);
    }

    public function validateConfiguration(ExtensionInterface $extension, $configuration)
    {
        if (!$configuration) {
            throw new \LogicException(sprintf('The extension with alias "%s" does not have its getConfiguration() method setup', $extension->getAlias()));
        }

        if (!$configuration instanceof ConfigurationInterface) {
            throw new \LogicException(sprintf('Configuration class "%s" should implement ConfigurationInterface in order to be dumpable', \get_class($configuration)));
        }
    }

    private function initializeBundles()
    {
        // Re-build bundle manually to initialize DI extensions that can be extended by other bundles in their build() method
        // as this method is not called when the container is loaded from the cache.
        $container = $this->getContainerBuilder();
        $bundles = $this->getContainer()->get('kernel')->getBundles();
        foreach ($bundles as $bundle) {
            if ($extension = $bundle->getContainerExtension()) {
                $container->registerExtension($extension);
            }
        }

        foreach ($bundles as $bundle) {
            $bundle->build($container);
        }

        return $bundles;
    }
}
