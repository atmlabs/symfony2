<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Security\Core\Tests\User;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\Ldap\Exception\ConnectionException;
use Symfony2\Component\Security\Core\User\LdapUserProvider;

/**
 * @requires extension ldap
 */
class LdapUserProviderTest extends TestCase
{
    /**
     * @expectedException \Symfony2\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testLoadUserByUsernameFailsIfCantConnectToLdap()
    {
        $ldap = $this->getMockBuilder('Symfony2\Component\Ldap\LdapClientInterface')->getMock();
        $ldap
            ->expects($this->once())
            ->method('bind')
            ->will($this->throwException(new ConnectionException()))
        ;

        $provider = new LdapUserProvider($ldap, 'ou=MyBusiness,dc=symfony,dc=com');
        $provider->loadUserByUsername('foo');
    }

    /**
     * @expectedException \Symfony2\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testLoadUserByUsernameFailsIfNoLdapEntries()
    {
        $ldap = $this->getMockBuilder('Symfony2\Component\Ldap\LdapClientInterface')->getMock();
        $ldap
            ->expects($this->once())
            ->method('escape')
            ->will($this->returnValue('foo'))
        ;

        $provider = new LdapUserProvider($ldap, 'ou=MyBusiness,dc=symfony,dc=com');
        $provider->loadUserByUsername('foo');
    }

    /**
     * @expectedException \Symfony2\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testLoadUserByUsernameFailsIfMoreThanOneLdapEntry()
    {
        $ldap = $this->getMockBuilder('Symfony2\Component\Ldap\LdapClientInterface')->getMock();
        $ldap
            ->expects($this->once())
            ->method('escape')
            ->will($this->returnValue('foo'))
        ;
        $ldap
            ->expects($this->once())
            ->method('find')
            ->will($this->returnValue(array(
                array(),
                array(),
                'count' => 2,
            )))
        ;

        $provider = new LdapUserProvider($ldap, 'ou=MyBusiness,dc=symfony,dc=com');
        $provider->loadUserByUsername('foo');
    }

    public function testSuccessfulLoadUserByUsername()
    {
        $ldap = $this->getMockBuilder('Symfony2\Component\Ldap\LdapClientInterface')->getMock();
        $ldap
            ->expects($this->once())
            ->method('escape')
            ->will($this->returnValue('foo'))
        ;
        $ldap
            ->expects($this->once())
            ->method('find')
            ->will($this->returnValue(array(
                array(
                    'sAMAccountName' => 'foo',
                    'userpassword' => 'bar',
                ),
                'count' => 1,
            )))
        ;

        $provider = new LdapUserProvider($ldap, 'ou=MyBusiness,dc=symfony,dc=com');
        $this->assertInstanceOf(
            'Symfony2\Component\Security\Core\User\User',
            $provider->loadUserByUsername('foo')
        );
    }
}
