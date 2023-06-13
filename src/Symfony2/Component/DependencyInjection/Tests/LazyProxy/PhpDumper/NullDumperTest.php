<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\DependencyInjection\Tests\LazyProxy\PhpDumper;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\DependencyInjection\Definition;
use Symfony2\Component\DependencyInjection\LazyProxy\PhpDumper\NullDumper;

/**
 * Tests for {@see \Symfony2\Component\DependencyInjection\PhpDumper\NullDumper}.
 *
 * @author Marco Pivetta <ocramius@gmail.com>
 */
class NullDumperTest extends TestCase
{
    public function testNullDumper()
    {
        $dumper = new NullDumper();
        $definition = new Definition('stdClass');

        $this->assertFalse($dumper->isProxyCandidate($definition));
        $this->assertSame('', $dumper->getProxyFactoryCode($definition, 'foo'));
        $this->assertSame('', $dumper->getProxyCode($definition));
    }
}
