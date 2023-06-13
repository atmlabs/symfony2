<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Translation\Tests\Dumper;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\Translation\Dumper\QtFileDumper;
use Symfony2\Component\Translation\MessageCatalogue;

class QtFileDumperTest extends TestCase
{
    public function testFormatCatalogue()
    {
        $catalogue = new MessageCatalogue('en');
        $catalogue->add(array('foo' => 'bar'), 'resources');

        $dumper = new QtFileDumper();

        $this->assertStringEqualsFile(__DIR__.'/../fixtures/resources.ts', $dumper->formatCatalogue($catalogue, 'resources'));
    }
}
