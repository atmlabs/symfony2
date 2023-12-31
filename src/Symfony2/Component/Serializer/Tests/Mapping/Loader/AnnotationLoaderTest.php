<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Serializer\Tests\Mapping\Loader;

use Doctrine\Common\Annotations\AnnotationReader;
use PHPUnit\Framework\TestCase;
use Symfony2\Component\Serializer\Mapping\ClassMetadata;
use Symfony2\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony2\Component\Serializer\Tests\Mapping\TestClassMetadataFactory;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class AnnotationLoaderTest extends TestCase
{
    /**
     * @var AnnotationLoader
     */
    private $loader;

    protected function setUp()
    {
        $this->loader = new AnnotationLoader(new AnnotationReader());
    }

    public function testInterface()
    {
        $this->assertInstanceOf('Symfony2\Component\Serializer\Mapping\Loader\LoaderInterface', $this->loader);
    }

    public function testLoadClassMetadataReturnsTrueIfSuccessful()
    {
        $classMetadata = new ClassMetadata('Symfony2\Component\Serializer\Tests\Fixtures\GroupDummy');

        $this->assertTrue($this->loader->loadClassMetadata($classMetadata));
    }

    public function testLoadClassMetadata()
    {
        $classMetadata = new ClassMetadata('Symfony2\Component\Serializer\Tests\Fixtures\GroupDummy');
        $this->loader->loadClassMetadata($classMetadata);

        $this->assertEquals(TestClassMetadataFactory::createClassMetadata(), $classMetadata);
    }

    public function testLoadClassMetadataAndMerge()
    {
        $classMetadata = new ClassMetadata('Symfony2\Component\Serializer\Tests\Fixtures\GroupDummy');
        $parentClassMetadata = new ClassMetadata('Symfony2\Component\Serializer\Tests\Fixtures\GroupDummyParent');

        $this->loader->loadClassMetadata($parentClassMetadata);
        $classMetadata->merge($parentClassMetadata);

        $this->loader->loadClassMetadata($classMetadata);

        $this->assertEquals(TestClassMetadataFactory::createClassMetadata(true), $classMetadata);
    }
}
