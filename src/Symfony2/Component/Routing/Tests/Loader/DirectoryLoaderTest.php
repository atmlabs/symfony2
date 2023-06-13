<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Routing\Tests\Loader;

use Symfony2\Component\Config\FileLocator;
use Symfony2\Component\Config\Loader\LoaderResolver;
use Symfony2\Component\Routing\Loader\AnnotationFileLoader;
use Symfony2\Component\Routing\Loader\DirectoryLoader;
use Symfony2\Component\Routing\Loader\YamlFileLoader;
use Symfony2\Component\Routing\RouteCollection;

class DirectoryLoaderTest extends AbstractAnnotationLoaderTest
{
    private $loader;
    private $reader;

    protected function setUp()
    {
        parent::setUp();

        $locator = new FileLocator();
        $this->reader = $this->getReader();
        $this->loader = new DirectoryLoader($locator);
        $resolver = new LoaderResolver(array(
            new YamlFileLoader($locator),
            new AnnotationFileLoader($locator, $this->getClassLoader($this->reader)),
            $this->loader,
        ));
        $this->loader->setResolver($resolver);
    }

    public function testLoadDirectory()
    {
        $collection = $this->loader->load(__DIR__.'/../Fixtures/directory', 'directory');
        $this->verifyCollection($collection);
    }

    public function testImportDirectory()
    {
        $collection = $this->loader->load(__DIR__.'/../Fixtures/directory_import', 'directory');
        $this->verifyCollection($collection);
    }

    private function verifyCollection(RouteCollection $collection)
    {
        $routes = $collection->all();

        $this->assertCount(3, $routes, 'Three routes are loaded');
        $this->assertContainsOnly('Symfony\Component\Routing\Route', $routes);

        for ($i = 1; $i <= 3; ++$i) {
            $this->assertSame('/route/'.$i, $routes['route'.$i]->getPath());
        }
    }

    public function testSupports()
    {
        $fixturesDir = __DIR__.'/../Fixtures';

        $this->assertFalse($this->loader->supports($fixturesDir), '->supports(*) returns false');

        $this->assertTrue($this->loader->supports($fixturesDir, 'directory'), '->supports(*, "directory") returns true');
        $this->assertFalse($this->loader->supports($fixturesDir, 'foo'), '->supports(*, "foo") returns false');
    }
}
