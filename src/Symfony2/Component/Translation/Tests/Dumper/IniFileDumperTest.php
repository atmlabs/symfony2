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
use Symfony2\Component\Translation\Dumper\IniFileDumper;
use Symfony2\Component\Translation\MessageCatalogue;

class IniFileDumperTest extends TestCase
{
    public function testFormatCatalogue()
    {
        $catalogue = new MessageCatalogue('en');
        $catalogue->add(array('foo' => 'bar'));

        $dumper = new IniFileDumper();

        $this->assertStringEqualsFile(__DIR__.'/../fixtures/resources.ini', $dumper->formatCatalogue($catalogue, 'messages'));
    }
}
