<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\HttpKernel\Tests\DataCollector\Util;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\HttpKernel\DataCollector\Util\ValueExporter;

class ValueExporterTest extends TestCase
{
    /**
     * @var ValueExporter
     */
    private $valueExporter;

    protected function setUp()
    {
        $this->valueExporter = new ValueExporter();
    }

    public function testDateTime()
    {
        $dateTime = new \DateTime('2014-06-10 07:35:40', new \DateTimeZone('UTC'));
        $this->assertSame('Object(DateTime) - 2014-06-10T07:35:40+00:00', $this->valueExporter->exportValue($dateTime));
    }

    /**
     * @requires PHP 5.5
     */
    public function testDateTimeImmutable()
    {
        $dateTime = new \DateTimeImmutable('2014-06-10 07:35:40', new \DateTimeZone('UTC'));
        $this->assertSame('Object(DateTimeImmutable) - 2014-06-10T07:35:40+00:00', $this->valueExporter->exportValue($dateTime));
    }

    public function testIncompleteClass()
    {
        $foo = new \__PHP_Incomplete_Class();
        $array = new \ArrayObject($foo);
        $array['__PHP_Incomplete_Class_Name'] = 'AppBundle/Foo';
        $this->assertSame('__PHP_Incomplete_Class(AppBundle/Foo)', $this->valueExporter->exportValue($foo));
    }
}
