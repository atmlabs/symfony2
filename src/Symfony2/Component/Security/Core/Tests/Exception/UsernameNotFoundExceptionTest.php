<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Security\Core\Tests\Exception;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\Security\Core\Exception\UsernameNotFoundException;

class UsernameNotFoundExceptionTest extends TestCase
{
    public function testGetMessageData()
    {
        $exception = new UsernameNotFoundException('Username could not be found.');
        $this->assertEquals(array('{{ username }}' => null), $exception->getMessageData());
        $exception->setUsername('username');
        $this->assertEquals(array('{{ username }}' => 'username'), $exception->getMessageData());
    }
}
