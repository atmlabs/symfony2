<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Serializer\Tests\Mapping\Factory;

use Doctrine\Common\Annotations\AnnotationReader;
use PHPUnit\Framework\TestCase;
use Symfony2\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony2\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony2\Component\Serializer\Mapping\Loader\LoaderChain;
use Symfony2\Component\Serializer\Tests\Mapping\TestClassMetadataFactory;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class ClassMetadataFactoryTest extends TestCase
{
    public function testInterface()
    {
        $classMetadata = new ClassMetadataFactory(new LoaderChain(array()));
        $this->assertInstanceOf('Symfony2\Component\Serializer\Mapping\Factory\ClassMetadataFactory', $classMetadata);
    }

    public function testGetMetadataFor()
    {
        $factory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $classMetadata = $factory->getMetadataFor('Symfony2\Component\Serializer\Tests\Fixtures\GroupDummy');

        $this->assertEquals(TestClassMetadataFactory::createClassMetadata(true, true), $classMetadata);
    }

    public function testHasMetadataFor()
    {
        $factory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()));
        $this->assertTrue($factory->hasMetadataFor('Symfony2\Component\Serializer\Tests\Fixtures\GroupDummy'));
        $this->assertTrue($factory->hasMetadataFor('Symfony2\Component\Serializer\Tests\Fixtures\GroupDummyParent'));
        $this->assertTrue($factory->hasMetadataFor('Symfony2\Component\Serializer\Tests\Fixtures\GroupDummyInterface'));
        $this->assertFalse($factory->hasMetadataFor('Dunglas\Entity'));
    }

    public function testCacheExists()
    {
        $cache = $this->getMockBuilder('Doctrine\Common\Cache\Cache')->getMock();
        $cache
            ->expects($this->once())
            ->method('fetch')
            ->will($this->returnValue('foo'))
        ;

        $factory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()), $cache);
        $this->assertEquals('foo', $factory->getMetadataFor('Symfony2\Component\Serializer\Tests\Fixtures\GroupDummy'));
    }

    public function testCacheNotExists()
    {
        $cache = $this->getMockBuilder('Doctrine\Common\Cache\Cache')->getMock();
        $cache
            ->method('fetch')
            ->will($this->returnValue(false))
        ;

        $cache
            ->method('save')
        ;

        $factory = new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader()), $cache);
        $metadata = $factory->getMetadataFor('Symfony2\Component\Serializer\Tests\Fixtures\GroupDummy');

        $this->assertEquals(TestClassMetadataFactory::createClassMetadata(true, true), $metadata);
    }
}
