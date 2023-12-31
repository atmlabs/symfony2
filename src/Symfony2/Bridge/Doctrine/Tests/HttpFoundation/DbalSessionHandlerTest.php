<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bridge\Doctrine\Tests\HttpFoundation;

use PHPUnit\Framework\TestCase;
use Symfony2\Bridge\Doctrine\HttpFoundation\DbalSessionHandler;

/**
 * Test class for DbalSessionHandler.
 *
 * @author Drak <drak@zikula.org>
 */
class DbalSessionHandlerTest extends TestCase
{
    public function testConstruct()
    {
        $connection = $this->getMockBuilder('Doctrine\DBAL\Connection')->disableOriginalConstructor()->getMock();
        $handler = new DbalSessionHandler($connection);

        $this->assertInstanceOf('Symfony2\Bridge\Doctrine\HttpFoundation\DbalSessionHandler', $handler);
    }
}
