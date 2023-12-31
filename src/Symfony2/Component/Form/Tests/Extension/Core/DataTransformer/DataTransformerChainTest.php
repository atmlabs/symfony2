<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Form\Tests\Extension\Core\DataTransformer;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\Form\Extension\Core\DataTransformer\DataTransformerChain;

class DataTransformerChainTest extends TestCase
{
    public function testTransform()
    {
        $transformer1 = $this->getMockBuilder('Symfony2\Component\Form\DataTransformerInterface')->getMock();
        $transformer1->expects($this->once())
            ->method('transform')
            ->with($this->identicalTo('foo'))
            ->will($this->returnValue('bar'));
        $transformer2 = $this->getMockBuilder('Symfony2\Component\Form\DataTransformerInterface')->getMock();
        $transformer2->expects($this->once())
            ->method('transform')
            ->with($this->identicalTo('bar'))
            ->will($this->returnValue('baz'));

        $chain = new DataTransformerChain(array($transformer1, $transformer2));

        $this->assertEquals('baz', $chain->transform('foo'));
    }

    public function testReverseTransform()
    {
        $transformer2 = $this->getMockBuilder('Symfony2\Component\Form\DataTransformerInterface')->getMock();
        $transformer2->expects($this->once())
            ->method('reverseTransform')
            ->with($this->identicalTo('foo'))
            ->will($this->returnValue('bar'));
        $transformer1 = $this->getMockBuilder('Symfony2\Component\Form\DataTransformerInterface')->getMock();
        $transformer1->expects($this->once())
            ->method('reverseTransform')
            ->with($this->identicalTo('bar'))
            ->will($this->returnValue('baz'));

        $chain = new DataTransformerChain(array($transformer1, $transformer2));

        $this->assertEquals('baz', $chain->reverseTransform('foo'));
    }
}
