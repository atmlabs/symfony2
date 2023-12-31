<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Config\Tests\Loader;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\Config\Loader\FileLoader;
use Symfony2\Component\Config\Loader\LoaderResolver;

class FileLoaderTest extends TestCase
{
    public function testImportWithFileLocatorDelegation()
    {
        $locatorMock = $this->getMockBuilder('Symfony2\Component\Config\FileLocatorInterface')->getMock();

        $locatorMockForAdditionalLoader = $this->getMockBuilder('Symfony2\Component\Config\FileLocatorInterface')->getMock();
        $locatorMockForAdditionalLoader->expects($this->any())->method('locate')->will($this->onConsecutiveCalls(
                array('path/to/file1'),                    // Default
                array('path/to/file1', 'path/to/file2'),   // First is imported
                array('path/to/file1', 'path/to/file2'),   // Second is imported
                array('path/to/file1'),                    // Exception
                array('path/to/file1', 'path/to/file2')    // Exception
                ));

        $fileLoader = new TestFileLoader($locatorMock);
        $fileLoader->setSupports(false);
        $fileLoader->setCurrentDir('.');

        $additionalLoader = new TestFileLoader($locatorMockForAdditionalLoader);
        $additionalLoader->setCurrentDir('.');

        $fileLoader->setResolver($loaderResolver = new LoaderResolver(array($fileLoader, $additionalLoader)));

        // Default case
        $this->assertSame('path/to/file1', $fileLoader->import('my_resource'));

        // Check first file is imported if not already loading
        $this->assertSame('path/to/file1', $fileLoader->import('my_resource'));

        // Check second file is imported if first is already loading
        $fileLoader->addLoading('path/to/file1');
        $this->assertSame('path/to/file2', $fileLoader->import('my_resource'));

        // Check exception throws if first (and only available) file is already loading
        try {
            $fileLoader->import('my_resource');
            $this->fail('->import() throws a FileLoaderImportCircularReferenceException if the resource is already loading');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Symfony2\Component\Config\Exception\FileLoaderImportCircularReferenceException', $e, '->import() throws a FileLoaderImportCircularReferenceException if the resource is already loading');
        }

        // Check exception throws if all files are already loading
        try {
            $fileLoader->addLoading('path/to/file2');
            $fileLoader->import('my_resource');
            $this->fail('->import() throws a FileLoaderImportCircularReferenceException if the resource is already loading');
        } catch (\Exception $e) {
            $this->assertInstanceOf('Symfony2\Component\Config\Exception\FileLoaderImportCircularReferenceException', $e, '->import() throws a FileLoaderImportCircularReferenceException if the resource is already loading');
        }
    }
}

class TestFileLoader extends FileLoader
{
    private $supports = true;

    public function load($resource, $type = null)
    {
        return $resource;
    }

    public function supports($resource, $type = null)
    {
        return $this->supports;
    }

    public function addLoading($resource)
    {
        self::$loading[$resource] = true;
    }

    public function removeLoading($resource)
    {
        unset(self::$loading[$resource]);
    }

    public function clearLoading()
    {
        self::$loading = array();
    }

    public function setSupports($supports)
    {
        $this->supports = $supports;
    }
}
