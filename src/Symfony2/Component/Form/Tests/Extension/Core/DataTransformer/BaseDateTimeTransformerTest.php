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

class BaseDateTimeTransformerTest extends TestCase
{
    /**
     * @expectedException \Symfony2\Component\Form\Exception\InvalidArgumentException
     * @expectedExceptionMessage this_timezone_does_not_exist
     */
    public function testConstructFailsIfInputTimezoneIsInvalid()
    {
        $this->getMockBuilder('Symfony2\Component\Form\Extension\Core\DataTransformer\BaseDateTimeTransformer')->setConstructorArgs(array('this_timezone_does_not_exist'))->getMock();
    }

    /**
     * @expectedException \Symfony2\Component\Form\Exception\InvalidArgumentException
     * @expectedExceptionMessage that_timezone_does_not_exist
     */
    public function testConstructFailsIfOutputTimezoneIsInvalid()
    {
        $this->getMockBuilder('Symfony2\Component\Form\Extension\Core\DataTransformer\BaseDateTimeTransformer')->setConstructorArgs(array(null, 'that_timezone_does_not_exist'))->getMock();
    }
}
