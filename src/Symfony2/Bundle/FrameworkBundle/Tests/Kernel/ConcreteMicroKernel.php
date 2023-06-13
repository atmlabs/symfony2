<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\FrameworkBundle\Tests\Kernel;

use Symfony2\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony2\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony2\Component\Config\Loader\LoaderInterface;
use Symfony2\Component\DependencyInjection\ContainerBuilder;
use Symfony2\Component\Filesystem\Filesystem;
use Symfony2\Component\HttpFoundation\Response;
use Symfony2\Component\HttpKernel\Kernel;
use Symfony2\Component\Routing\RouteCollectionBuilder;

class ConcreteMicroKernel extends Kernel
{
    use MicroKernelTrait;

    private $cacheDir;

    public function halloweenAction()
    {
        return new Response('halloween');
    }

    public function registerBundles()
    {
        return array(
            new FrameworkBundle(),
        );
    }

    public function getCacheDir()
    {
        return $this->cacheDir = sys_get_temp_dir().'/sf_micro_kernel';
    }

    public function getLogDir()
    {
        return $this->cacheDir;
    }

    public function __destruct()
    {
        $fs = new Filesystem();
        $fs->remove($this->cacheDir);
    }

    protected function configureRoutes(RouteCollectionBuilder $routes)
    {
        $routes->add('/', 'kernel:halloweenAction');
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader)
    {
        $c->loadFromExtension('framework', array(
            'secret' => '$ecret',
        ));

        $c->setParameter('halloween', 'Have a great day!');
        $c->register('halloween', 'stdClass');
    }
}
