<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Routing\Tests\Annotation;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\Routing\Annotation\Route;

class RouteTest extends TestCase
{
    /**
     * @expectedException \BadMethodCallException
     */
    public function testInvalidRouteParameter()
    {
        $route = new Route(array('foo' => 'bar'));
    }

    /**
     * @dataProvider getValidParameters
     */
    public function testRouteParameters($parameter, $value, $getter)
    {
        $route = new Route(array($parameter => $value));
        $this->assertEquals($route->$getter(), $value);
    }

    public function getValidParameters()
    {
        return array(
            array('value', '/Blog', 'getPath'),
            array('requirements', array('locale' => 'en'), 'getRequirements'),
            array('options', array('compiler_class' => 'RouteCompiler'), 'getOptions'),
            array('name', 'blog_index', 'getName'),
            array('defaults', array('_controller' => 'MyBlogBundle:Blog:index'), 'getDefaults'),
            array('schemes', array('https'), 'getSchemes'),
            array('methods', array('GET', 'POST'), 'getMethods'),
            array('host', '{locale}.example.com', 'getHost'),
            array('condition', 'context.getMethod() == "GET"', 'getCondition'),
        );
    }

    /**
     * @group legacy
     */
    public function testLegacyGetPattern()
    {
        $route = new Route(array('value' => '/Blog'));
        $this->assertEquals($route->getPattern(), '/Blog');
    }
}
