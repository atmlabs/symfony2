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
use Symfony2\Component\Security\Core\User\User;

class UserTest extends TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructorException()
    {
        new User('', 'superpass');
    }

    public function testGetRoles()
    {
        $user = new User('fabien', 'superpass');
        $this->assertEquals(array(), $user->getRoles());

        $user = new User('fabien', 'superpass', array('ROLE_ADMIN'));
        $this->assertEquals(array('ROLE_ADMIN'), $user->getRoles());
    }

    public function testGetPassword()
    {
        $user = new User('fabien', 'superpass');
        $this->assertEquals('superpass', $user->getPassword());
    }

    public function testGetUsername()
    {
        $user = new User('fabien', 'superpass');
        $this->assertEquals('fabien', $user->getUsername());
    }

    public function testGetSalt()
    {
        $user = new User('fabien', 'superpass');
        $this->assertEquals('', $user->getSalt());
    }

    public function testIsAccountNonExpired()
    {
        $user = new User('fabien', 'superpass');
        $this->assertTrue($user->isAccountNonExpired());

        $user = new User('fabien', 'superpass', array(), true, false);
        $this->assertFalse($user->isAccountNonExpired());
    }

    public function testIsCredentialsNonExpired()
    {
        $user = new User('fabien', 'superpass');
        $this->assertTrue($user->isCredentialsNonExpired());

        $user = new User('fabien', 'superpass', array(), true, true, false);
        $this->assertFalse($user->isCredentialsNonExpired());
    }

    public function testIsAccountNonLocked()
    {
        $user = new User('fabien', 'superpass');
        $this->assertTrue($user->isAccountNonLocked());

        $user = new User('fabien', 'superpass', array(), true, true, true, false);
        $this->assertFalse($user->isAccountNonLocked());
    }

    public function testIsEnabled()
    {
        $user = new User('fabien', 'superpass');
        $this->assertTrue($user->isEnabled());

        $user = new User('fabien', 'superpass', array(), false);
        $this->assertFalse($user->isEnabled());
    }

    public function testEraseCredentials()
    {
        $user = new User('fabien', 'superpass');
        $user->eraseCredentials();
        $this->assertEquals('superpass', $user->getPassword());
    }

    public function testToString()
    {
        $user = new User('fabien', 'superpass');
        $this->assertEquals('fabien', (string) $user);
    }
}
