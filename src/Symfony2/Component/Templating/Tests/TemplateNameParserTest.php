<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Templating\Tests;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\Templating\TemplateNameParser;
use Symfony2\Component\Templating\TemplateReference;

class TemplateNameParserTest extends TestCase
{
    protected $parser;

    protected function setUp()
    {
        $this->parser = new TemplateNameParser();
    }

    protected function tearDown()
    {
        $this->parser = null;
    }

    /**
     * @dataProvider getLogicalNameToTemplateProvider
     */
    public function testParse($name, $ref)
    {
        $template = $this->parser->parse($name);

        $this->assertEquals($template->getLogicalName(), $ref->getLogicalName());
        $this->assertEquals($template->getLogicalName(), $name);
    }

    public function getLogicalNameToTemplateProvider()
    {
        return array(
            array('/path/to/section/name.engine', new TemplateReference('/path/to/section/name.engine', 'engine')),
            array('name.engine', new TemplateReference('name.engine', 'engine')),
            array('name', new TemplateReference('name')),
        );
    }
}
