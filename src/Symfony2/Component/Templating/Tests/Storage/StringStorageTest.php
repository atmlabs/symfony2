<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Templating\Tests\Storage;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\Templating\Storage\StringStorage;

class StringStorageTest extends TestCase
{
    public function testGetContent()
    {
        $storage = new StringStorage('foo');
        $this->assertInstanceOf('Symfony2\Component\Templating\Storage\Storage', $storage, 'StringStorage is an instance of Storage');
        $storage = new StringStorage('foo');
        $this->assertEquals('foo', $storage->getContent(), '->getContent() returns the content of the template');
    }
}
