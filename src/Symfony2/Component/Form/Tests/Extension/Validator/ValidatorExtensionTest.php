<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Form\Tests\Extension\Validator;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony2\Component\Validator\ValidatorInterface;

class ValidatorExtensionTest extends TestCase
{
    public function test2Dot5ValidationApi()
    {
        $validator = $this->getMockBuilder('Symfony2\Component\Validator\Validator\RecursiveValidator')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata = $this->getMockBuilder('Symfony2\Component\Validator\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $validator->expects($this->once())
            ->method('getMetadataFor')
            ->with($this->identicalTo('Symfony2\Component\Form\Form'))
            ->will($this->returnValue($metadata));

        // Verify that the constraints are added
        $metadata->expects($this->once())
            ->method('addConstraint')
            ->with($this->isInstanceOf('Symfony2\Component\Form\Extension\Validator\Constraints\Form'));

        $metadata->expects($this->once())
            ->method('addPropertyConstraint')
            ->with('children', $this->isInstanceOf('Symfony2\Component\Validator\Constraints\Valid'));

        if ($validator instanceof ValidatorInterface) {
            $validator
                ->expects($this->never())
                ->method('getMetadataFactory');
        }

        $extension = new ValidatorExtension($validator);
        $guesser = $extension->loadTypeGuesser();

        $this->assertInstanceOf('Symfony2\Component\Form\Extension\Validator\ValidatorTypeGuesser', $guesser);
    }

    /**
     * @group legacy
     */
    public function test2Dot4ValidationApi()
    {
        $factory = $this->getMockBuilder('Symfony2\Component\Validator\Mapping\Factory\MetadataFactoryInterface')->getMock();
        $validator = $this->getMockBuilder('Symfony2\Component\Validator\ValidatorInterface')->getMock();
        $metadata = $this->getMockBuilder('Symfony2\Component\Validator\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();

        $validator->expects($this->any())
            ->method('getMetadataFactory')
            ->will($this->returnValue($factory));

        $factory->expects($this->once())
            ->method('getMetadataFor')
            ->with($this->identicalTo('Symfony2\Component\Form\Form'))
            ->will($this->returnValue($metadata));

        // Verify that the constraints are added
        $metadata->expects($this->once())
            ->method('addConstraint')
            ->with($this->isInstanceOf('Symfony2\Component\Form\Extension\Validator\Constraints\Form'));

        $metadata->expects($this->once())
            ->method('addPropertyConstraint')
            ->with('children', $this->isInstanceOf('Symfony2\Component\Validator\Constraints\Valid'));

        $extension = new ValidatorExtension($validator);
        $guesser = $extension->loadTypeGuesser();

        $this->assertInstanceOf('Symfony2\Component\Form\Extension\Validator\ValidatorTypeGuesser', $guesser);
    }

    /**
     * @expectedException \Symfony2\Component\Form\Exception\UnexpectedTypeException
     * @group legacy
     */
    public function testInvalidValidatorInterface()
    {
        new ValidatorExtension(null);
    }
}
