<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Validator\Tests\Util;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\Validator\Util\PropertyPath;

class PropertyPathTest extends TestCase
{
    /**
     * @dataProvider provideAppendPaths
     */
    public function testAppend($basePath, $subPath, $expectedPath, $message)
    {
        $this->assertSame($expectedPath, PropertyPath::append($basePath, $subPath), $message);
    }

    public function provideAppendPaths()
    {
        return array(
            array('foo', '', 'foo', 'It returns the basePath if subPath is empty'),
            array('', 'bar', 'bar', 'It returns the subPath if basePath is empty'),
            array('foo', 'bar', 'foo.bar', 'It append the subPath to the basePath'),
            array('foo', '[bar]', 'foo[bar]', 'It does not include the dot separator if subPath uses the array notation'),
            array('0', 'bar', '0.bar', 'Leading zeros are kept.'),
        );
    }
}
