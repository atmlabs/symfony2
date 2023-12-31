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
use Symfony2\Component\EventDispatcher\EventDispatcher;
use Symfony2\Component\HttpFoundation\Cookie;
use Symfony2\Component\HttpFoundation\Request;
use Symfony2\Component\HttpFoundation\Response;
use Symfony2\Component\HttpKernel\DataCollector\RequestDataCollector;
use Symfony2\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony2\Component\HttpKernel\HttpKernel;
use Symfony2\Component\HttpKernel\HttpKernelInterface;

class RequestDataCollectorTest extends TestCase
{
    public function testCollect()
    {
        $c = new RequestDataCollector();

        $c->collect($this->createRequest(), $this->createResponse());

        $attributes = $c->getRequestAttributes();

        $this->assertSame('request', $c->getName());
        $this->assertInstanceOf('Symfony2\Component\HttpFoundation\ParameterBag', $c->getRequestHeaders());
        $this->assertInstanceOf('Symfony2\Component\HttpFoundation\ParameterBag', $c->getRequestServer());
        $this->assertInstanceOf('Symfony2\Component\HttpFoundation\ParameterBag', $c->getRequestCookies());
        $this->assertInstanceOf('Symfony2\Component\HttpFoundation\ParameterBag', $attributes);
        $this->assertInstanceOf('Symfony2\Component\HttpFoundation\ParameterBag', $c->getRequestRequest());
        $this->assertInstanceOf('Symfony2\Component\HttpFoundation\ParameterBag', $c->getRequestQuery());
        $this->assertSame('html', $c->getFormat());
        $this->assertSame('foobar', $c->getRoute());
        $this->assertSame(array('name' => 'foo'), $c->getRouteParams());
        $this->assertSame(array(), $c->getSessionAttributes());
        $this->assertSame('en', $c->getLocale());
        $this->assertRegExp('/Resource\(stream#\d+\)/', $attributes->get('resource'));
        $this->assertSame('Object(stdClass)', $attributes->get('object'));

        $this->assertInstanceOf('Symfony2\Component\HttpFoundation\ParameterBag', $c->getResponseHeaders());
        $this->assertSame('OK', $c->getStatusText());
        $this->assertSame(200, $c->getStatusCode());
        $this->assertSame('application/json', $c->getContentType());
    }

    /**
     * Test various types of controller callables.
     */
    public function testControllerInspection()
    {
        // make sure we always match the line number
        $r1 = new \ReflectionMethod($this, 'testControllerInspection');
        $r2 = new \ReflectionMethod($this, 'staticControllerMethod');
        $r3 = new \ReflectionClass($this);
        // test name, callable, expected
        $controllerTests = array(
            array(
                '"Regular" callable',
                array($this, 'testControllerInspection'),
                array(
                    'class' => 'Symfony2\Component\HttpKernel\Tests\DataCollector\RequestDataCollectorTest',
                    'method' => 'testControllerInspection',
                    'file' => __FILE__,
                    'line' => $r1->getStartLine(),
                ),
            ),

            array(
                'Closure',
                function () { return 'foo'; },
                array(
                    'class' => __NAMESPACE__.'\{closure}',
                    'method' => null,
                    'file' => __FILE__,
                    'line' => __LINE__ - 5,
                ),
            ),

            array(
                'Static callback as string',
                'Symfony2\Component\HttpKernel\Tests\DataCollector\RequestDataCollectorTest::staticControllerMethod',
                'Symfony2\Component\HttpKernel\Tests\DataCollector\RequestDataCollectorTest::staticControllerMethod',
            ),

            array(
                'Static callable with instance',
                array($this, 'staticControllerMethod'),
                array(
                    'class' => 'Symfony2\Component\HttpKernel\Tests\DataCollector\RequestDataCollectorTest',
                    'method' => 'staticControllerMethod',
                    'file' => __FILE__,
                    'line' => $r2->getStartLine(),
                ),
            ),

            array(
                'Static callable with class name',
                array('Symfony2\Component\HttpKernel\Tests\DataCollector\RequestDataCollectorTest', 'staticControllerMethod'),
                array(
                    'class' => 'Symfony2\Component\HttpKernel\Tests\DataCollector\RequestDataCollectorTest',
                    'method' => 'staticControllerMethod',
                    'file' => __FILE__,
                    'line' => $r2->getStartLine(),
                ),
            ),

            array(
                'Callable with instance depending on __call()',
                array($this, 'magicMethod'),
                array(
                    'class' => 'Symfony2\Component\HttpKernel\Tests\DataCollector\RequestDataCollectorTest',
                    'method' => 'magicMethod',
                    'file' => 'n/a',
                    'line' => 'n/a',
                ),
            ),

            array(
                'Callable with class name depending on __callStatic()',
                array('Symfony2\Component\HttpKernel\Tests\DataCollector\RequestDataCollectorTest', 'magicMethod'),
                array(
                    'class' => 'Symfony2\Component\HttpKernel\Tests\DataCollector\RequestDataCollectorTest',
                    'method' => 'magicMethod',
                    'file' => 'n/a',
                    'line' => 'n/a',
                ),
            ),

            array(
                'Invokable controller',
                $this,
                array(
                    'class' => 'Symfony2\Component\HttpKernel\Tests\DataCollector\RequestDataCollectorTest',
                    'method' => null,
                    'file' => __FILE__,
                    'line' => $r3->getStartLine(),
                ),
            ),
        );

        $c = new RequestDataCollector();
        $request = $this->createRequest();
        $response = $this->createResponse();
        foreach ($controllerTests as $controllerTest) {
            $this->injectController($c, $controllerTest[1], $request);
            $c->collect($request, $response);
            $this->assertSame($controllerTest[2], $c->getController(), sprintf('Testing: %s', $controllerTest[0]));
        }
    }

    protected function createRequest()
    {
        $request = Request::create('http://test.com/foo?bar=baz');
        $request->attributes->set('foo', 'bar');
        $request->attributes->set('_route', 'foobar');
        $request->attributes->set('_route_params', array('name' => 'foo'));
        $request->attributes->set('resource', fopen(__FILE__, 'r'));
        $request->attributes->set('object', new \stdClass());

        return $request;
    }

    protected function createResponse()
    {
        $response = new Response();
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->setCookie(new Cookie('foo', 'bar', 1, '/foo', 'localhost', true, true));
        $response->headers->setCookie(new Cookie('bar', 'foo', new \DateTime('@946684800')));
        $response->headers->setCookie(new Cookie('bazz', 'foo', '2000-12-12'));

        return $response;
    }

    /**
     * Inject the given controller callable into the data collector.
     */
    protected function injectController($collector, $controller, $request)
    {
        $resolver = $this->getMockBuilder('Symfony2\\Component\\HttpKernel\\Controller\\ControllerResolverInterface')->getMock();
        $httpKernel = new HttpKernel(new EventDispatcher(), $resolver);
        $event = new FilterControllerEvent($httpKernel, $controller, $request, HttpKernelInterface::MASTER_REQUEST);
        $collector->onKernelController($event);
    }

    /**
     * Dummy method used as controller callable.
     */
    public static function staticControllerMethod()
    {
        throw new \LogicException('Unexpected method call');
    }

    /**
     * Magic method to allow non existing methods to be called and delegated.
     */
    public function __call($method, $args)
    {
        throw new \LogicException('Unexpected method call');
    }

    /**
     * Magic method to allow non existing methods to be called and delegated.
     */
    public static function __callStatic($method, $args)
    {
        throw new \LogicException('Unexpected method call');
    }

    public function __invoke()
    {
        throw new \LogicException('Unexpected method call');
    }
}
