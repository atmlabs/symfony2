<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\FrameworkBundle\Tests\Templating;

use Symfony2\Bundle\FrameworkBundle\Templating\GlobalVariables;
use Symfony2\Bundle\FrameworkBundle\Templating\PhpEngine;
use Symfony2\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony2\Component\DependencyInjection\Container;
use Symfony2\Component\HttpFoundation\Request;
use Symfony2\Component\HttpFoundation\RequestStack;
use Symfony2\Component\HttpFoundation\Session\Session;
use Symfony2\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony2\Component\Templating\TemplateNameParser;

class PhpEngineTest extends TestCase
{
    public function testEvaluateAddsAppGlobal()
    {
        $container = $this->getContainer();
        $loader = $this->getMockForAbstractClass('Symfony2\Component\Templating\Loader\Loader');
        $engine = new PhpEngine(new TemplateNameParser(), $container, $loader, $app = new GlobalVariables($container));
        $globals = $engine->getGlobals();
        $this->assertSame($app, $globals['app']);
    }

    public function testEvaluateWithoutAvailableRequest()
    {
        $container = new Container();
        $loader = $this->getMockForAbstractClass('Symfony2\Component\Templating\Loader\Loader');
        $engine = new PhpEngine(new TemplateNameParser(), $container, $loader, new GlobalVariables($container));

        $container->set('request_stack', null);

        $globals = $engine->getGlobals();
        $this->assertEmpty($globals['app']->getRequest());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetInvalidHelper()
    {
        $container = $this->getContainer();
        $loader = $this->getMockForAbstractClass('Symfony2\Component\Templating\Loader\Loader');
        $engine = new PhpEngine(new TemplateNameParser(), $container, $loader);

        $engine->get('non-existing-helper');
    }

    /**
     * Creates a Container with a Session-containing Request service.
     *
     * @return Container
     */
    protected function getContainer()
    {
        $container = new Container();
        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $stack = new RequestStack();
        $stack->push($request);

        $request->setSession($session);
        $container->set('request_stack', $stack);

        return $container;
    }
}
