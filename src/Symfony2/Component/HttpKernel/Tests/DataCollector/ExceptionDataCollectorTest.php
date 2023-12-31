<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\HttpKernel\Tests\DataCollector;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\Debug\Exception\FlattenException;
use Symfony2\Component\HttpFoundation\Request;
use Symfony2\Component\HttpFoundation\Response;
use Symfony2\Component\HttpKernel\DataCollector\ExceptionDataCollector;

class ExceptionDataCollectorTest extends TestCase
{
    public function testCollect()
    {
        $e = new \Exception('foo', 500);
        $c = new ExceptionDataCollector();
        $flattened = FlattenException::create($e);
        $trace = $flattened->getTrace();

        $this->assertFalse($c->hasException());

        $c->collect(new Request(), new Response(), $e);

        $this->assertTrue($c->hasException());
        $this->assertEquals($flattened, $c->getException());
        $this->assertSame('foo', $c->getMessage());
        $this->assertSame(500, $c->getCode());
        $this->assertSame('exception', $c->getName());
        $this->assertSame($trace, $c->getTrace());
    }
}
