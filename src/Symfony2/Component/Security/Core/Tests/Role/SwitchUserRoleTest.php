<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Security\Core\Tests\Role;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\Security\Core\Role\SwitchUserRole;

class SwitchUserRoleTest extends TestCase
{
    public function testGetSource()
    {
        $role = new SwitchUserRole('FOO', $token = $this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\Token\TokenInterface')->getMock());

        $this->assertSame($token, $role->getSource());
    }

    public function testGetRole()
    {
        $role = new SwitchUserRole('FOO', $this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\Token\TokenInterface')->getMock());

        $this->assertEquals('FOO', $role->getRole());
    }
}
