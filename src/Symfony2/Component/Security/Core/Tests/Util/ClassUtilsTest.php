<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Security\Core\Tests\Util
{
    use PHPUnit\Framework\TestCase;
    use Symfony2\Component\Security\Core\Util\ClassUtils;

    /**
     * @group legacy
     */
    class ClassUtilsTest extends TestCase
    {
        public static function dataGetClass()
        {
            return array(
                array('stdClass', 'stdClass'),
                array('Symfony2\Component\Security\Core\Util\ClassUtils', 'Symfony2\Component\Security\Core\Util\ClassUtils'),
                array('MyProject\Proxies\__CG__\stdClass', 'stdClass'),
                array('MyProject\Proxies\__CG__\OtherProject\Proxies\__CG__\stdClass', 'stdClass'),
                array('MyProject\Proxies\__CG__\Symfony2\Component\Security\Core\Tests\Util\ChildObject', 'Symfony2\Component\Security\Core\Tests\Util\ChildObject'),
                array(new TestObject(), 'Symfony2\Component\Security\Core\Tests\Util\TestObject'),
                array(new \Acme\DemoBundle\Proxy\__CG__\Symfony2\Component\Security\Core\Tests\Util\TestObject(), 'Symfony2\Component\Security\Core\Tests\Util\TestObject'),
            );
        }

        /**
         * @dataProvider dataGetClass
         */
        public function testGetRealClass($object, $expectedClassName)
        {
            $this->assertEquals($expectedClassName, ClassUtils::getRealClass($object));
        }
    }

    class TestObject
    {
    }
}

namespace Acme\DemoBundle\Proxy\__CG__\Symfony2\Component\Security\Core\Tests\Util
{
    use Symfony2\Component\Security\Core\Tests\Util\TestObject as BaseTestObject;

    class TestObject extends BaseTestObject
    {
    }
}
