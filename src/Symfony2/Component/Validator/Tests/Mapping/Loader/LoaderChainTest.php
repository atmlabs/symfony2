<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Validator\Tests\Mapping\Loader;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\Validator\Mapping\ClassMetadata;
use Symfony2\Component\Validator\Mapping\Loader\LoaderChain;

class LoaderChainTest extends TestCase
{
    public function testAllLoadersAreCalled()
    {
        $metadata = new ClassMetadata('\stdClass');

        $loader1 = $this->getMockBuilder('Symfony2\Component\Validator\Mapping\Loader\LoaderInterface')->getMock();
        $loader1->expects($this->once())
            ->method('loadClassMetadata')
            ->with($this->equalTo($metadata));

        $loader2 = $this->getMockBuilder('Symfony2\Component\Validator\Mapping\Loader\LoaderInterface')->getMock();
        $loader2->expects($this->once())
            ->method('loadClassMetadata')
            ->with($this->equalTo($metadata));

        $chain = new LoaderChain(array(
            $loader1,
            $loader2,
        ));

        $chain->loadClassMetadata($metadata);
    }

    public function testReturnsTrueIfAnyLoaderReturnedTrue()
    {
        $metadata = new ClassMetadata('\stdClass');

        $loader1 = $this->getMockBuilder('Symfony2\Component\Validator\Mapping\Loader\LoaderInterface')->getMock();
        $loader1->expects($this->any())
            ->method('loadClassMetadata')
            ->will($this->returnValue(true));

        $loader2 = $this->getMockBuilder('Symfony2\Component\Validator\Mapping\Loader\LoaderInterface')->getMock();
        $loader2->expects($this->any())
            ->method('loadClassMetadata')
            ->will($this->returnValue(false));

        $chain = new LoaderChain(array(
            $loader1,
            $loader2,
        ));

        $this->assertTrue($chain->loadClassMetadata($metadata));
    }

    public function testReturnsFalseIfNoLoaderReturnedTrue()
    {
        $metadata = new ClassMetadata('\stdClass');

        $loader1 = $this->getMockBuilder('Symfony2\Component\Validator\Mapping\Loader\LoaderInterface')->getMock();
        $loader1->expects($this->any())
            ->method('loadClassMetadata')
            ->will($this->returnValue(false));

        $loader2 = $this->getMockBuilder('Symfony2\Component\Validator\Mapping\Loader\LoaderInterface')->getMock();
        $loader2->expects($this->any())
            ->method('loadClassMetadata')
            ->will($this->returnValue(false));

        $chain = new LoaderChain(array(
            $loader1,
            $loader2,
        ));

        $this->assertFalse($chain->loadClassMetadata($metadata));
    }
}
