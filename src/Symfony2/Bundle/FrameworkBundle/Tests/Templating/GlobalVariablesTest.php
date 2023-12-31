<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\FrameworkBundle\Tests\Templating;

use Symfony2\Bundle\FrameworkBundle\Templating\GlobalVariables;
use Symfony2\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony2\Component\DependencyInjection\Container;

class GlobalVariablesTest extends TestCase
{
    private $container;
    private $globals;

    protected function setUp()
    {
        $this->container = new Container();
        $this->globals = new GlobalVariables($this->container);
    }

    /**
     * @group legacy
     */
    public function testLegacyGetSecurity()
    {
        $securityContext = $this->getMockBuilder('Symfony2\Component\Security\Core\SecurityContextInterface')->getMock();

        $this->assertNull($this->globals->getSecurity());
        $this->container->set('security.context', $securityContext);
        $this->assertSame($securityContext, $this->globals->getSecurity());
    }

    public function testGetUserNoTokenStorage()
    {
        $this->assertNull($this->globals->getUser());
    }

    public function testGetUserNoToken()
    {
        $tokenStorage = $this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface')->getMock();
        $this->container->set('security.token_storage', $tokenStorage);
        $this->assertNull($this->globals->getUser());
    }

    /**
     * @dataProvider getUserProvider
     */
    public function testGetUser($user, $expectedUser)
    {
        $tokenStorage = $this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface')->getMock();
        $token = $this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\Token\TokenInterface')->getMock();

        $this->container->set('security.token_storage', $tokenStorage);

        $token
            ->expects($this->once())
            ->method('getUser')
            ->will($this->returnValue($user));

        $tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->will($this->returnValue($token));

        $this->assertSame($expectedUser, $this->globals->getUser());
    }

    public function getUserProvider()
    {
        $user = $this->getMockBuilder('Symfony2\Component\Security\Core\User\UserInterface')->getMock();
        $std = new \stdClass();
        $token = $this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\Token\TokenInterface')->getMock();

        return array(
            array($user, $user),
            array($std, $std),
            array($token, $token),
            array('Anon.', null),
            array(null, null),
            array(10, null),
            array(true, null),
        );
    }
}
