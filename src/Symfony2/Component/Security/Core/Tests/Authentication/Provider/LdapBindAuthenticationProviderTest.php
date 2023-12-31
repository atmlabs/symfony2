<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Security\Core\Tests\Authentication\Provider;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\Ldap\Exception\ConnectionException;
use Symfony2\Component\Security\Core\Authentication\Provider\LdapBindAuthenticationProvider;
use Symfony2\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony2\Component\Security\Core\User\User;

/**
 * @requires extension ldap
 */
class LdapBindAuthenticationProviderTest extends TestCase
{
    /**
     * @expectedException        \Symfony2\Component\Security\Core\Exception\BadCredentialsException
     * @expectedExceptionMessage The presented password must not be empty.
     */
    public function testEmptyPasswordShouldThrowAnException()
    {
        $userProvider = $this->getMockBuilder('Symfony2\Component\Security\Core\User\UserProviderInterface')->getMock();
        $ldap = $this->getMockBuilder('Symfony2\Component\Ldap\LdapClientInterface')->getMock();
        $userChecker = $this->getMockBuilder('Symfony2\Component\Security\Core\User\UserCheckerInterface')->getMock();

        $provider = new LdapBindAuthenticationProvider($userProvider, $userChecker, 'key', $ldap);
        $reflection = new \ReflectionMethod($provider, 'checkAuthentication');
        $reflection->setAccessible(true);

        $reflection->invoke($provider, new User('foo', null), new UsernamePasswordToken('foo', '', 'key'));
    }

    /**
     * @expectedException        \Symfony2\Component\Security\Core\Exception\BadCredentialsException
     * @expectedExceptionMessage The presented password must not be empty.
     */
    public function testNullPasswordShouldThrowAnException()
    {
        $userProvider = $this->getMockBuilder('Symfony2\Component\Security\Core\User\UserProviderInterface')->getMock();
        $ldap = $this->getMockBuilder('Symfony2\Component\Ldap\LdapClientInterface')->getMock();
        $userChecker = $this->getMockBuilder('Symfony2\Component\Security\Core\User\UserCheckerInterface')->getMock();

        $provider = new LdapBindAuthenticationProvider($userProvider, $userChecker, 'key', $ldap);
        $reflection = new \ReflectionMethod($provider, 'checkAuthentication');
        $reflection->setAccessible(true);

        $reflection->invoke($provider, new User('foo', null), new UsernamePasswordToken('foo', null, 'key'));
    }

    /**
     * @expectedException        \Symfony2\Component\Security\Core\Exception\BadCredentialsException
     * @expectedExceptionMessage The presented password is invalid.
     */
    public function testBindFailureShouldThrowAnException()
    {
        $userProvider = $this->getMockBuilder('Symfony2\Component\Security\Core\User\UserProviderInterface')->getMock();
        $ldap = $this->getMockBuilder('Symfony2\Component\Ldap\LdapClientInterface')->getMock();
        $ldap
            ->expects($this->once())
            ->method('bind')
            ->will($this->throwException(new ConnectionException()))
        ;
        $userChecker = $this->getMockBuilder('Symfony2\Component\Security\Core\User\UserCheckerInterface')->getMock();

        $provider = new LdapBindAuthenticationProvider($userProvider, $userChecker, 'key', $ldap);
        $reflection = new \ReflectionMethod($provider, 'checkAuthentication');
        $reflection->setAccessible(true);

        $reflection->invoke($provider, new User('foo', null), new UsernamePasswordToken('foo', 'bar', 'key'));
    }

    public function testRetrieveUser()
    {
        $userProvider = $this->getMockBuilder('Symfony2\Component\Security\Core\User\UserProviderInterface')->getMock();
        $userProvider
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->with('foo')
        ;
        $ldap = $this->getMockBuilder('Symfony2\Component\Ldap\LdapClientInterface')->getMock();

        $userChecker = $this->getMockBuilder('Symfony2\Component\Security\Core\User\UserCheckerInterface')->getMock();

        $provider = new LdapBindAuthenticationProvider($userProvider, $userChecker, 'key', $ldap);
        $reflection = new \ReflectionMethod($provider, 'retrieveUser');
        $reflection->setAccessible(true);

        $reflection->invoke($provider, 'foo', new UsernamePasswordToken('foo', 'bar', 'key'));
    }
}
