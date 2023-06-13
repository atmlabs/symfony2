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
use Symfony2\Component\DependencyInjection\ContainerInterface;
use Symfony2\Component\Filesystem\Exception\IOException;
use Symfony2\Component\Filesystem\Filesystem;

class CompilerDebugDumpPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        $filename = self::getCompilerLogFilename($container);

        $filesystem = new Filesystem();
        $filesystem->dumpFile($filename, implode("\n", $container->getCompiler()->getLog()), null);
        try {
            $filesystem->chmod($filename, 0666, umask());
        } catch (IOException $e) {
            // discard chmod failure (some filesystem may not support it)
        }
    }

    public static function getCompilerLogFilename(ContainerInterface $container)
    {
        $class = $container->getParameter('kernel.container_class');

        return $container->getParameter('kernel.cache_dir').'/'.$class.'Compiler.log';
    }
}
