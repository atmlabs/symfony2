<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\HttpKernel\Tests;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\EventDispatcher\EventDispatcher;
use Symfony2\Component\HttpFoundation\RedirectResponse;
use Symfony2\Component\HttpFoundation\Request;
use Symfony2\Component\HttpFoundation\Response;
use Symfony2\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony2\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony2\Component\HttpKernel\HttpKernel;
use Symfony2\Component\HttpKernel\HttpKernelInterface;
use Symfony2\Component\HttpKernel\KernelEvents;

class HttpKernelTest extends TestCase
{
    /**
     * @expectedException \RuntimeException
     */
    public function testHandleWhenControllerThrowsAnExceptionAndCatchIsTrue()
    {
        $kernel = new HttpKernel(new EventDispatcher(), $this->getResolver(function () { throw new \RuntimeException(); }));

        $kernel->handle(new Request(), HttpKernelInterface::MASTER_REQUEST, true);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testHandleWhenControllerThrowsAnExceptionAndCatchIsFalseAndNoListenerIsRegistered()
    {
        $kernel = new HttpKernel(new EventDispatcher(), $this->getResolver(function () { throw new \RuntimeException(); }));

        $kernel->handle(new Request(), HttpKernelInterface::MASTER_REQUEST, false);
    }

    public function testHandleWhenControllerThrowsAnExceptionAndCatchIsTrueWithAHandlingListener()
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(KernelEvents::EXCEPTION, function ($event) {
            $event->setResponse(new Response($event->getException()->getMessage()));
        });

        $kernel = new HttpKernel($dispatcher, $this->getResolver(function () { throw new \RuntimeException('foo'); }));
        $response = $kernel->handle(new Request(), HttpKernelInterface::MASTER_REQUEST, true);

        $this->assertEquals('500', $response->getStatusCode());
        $this->assertEquals('foo', $response->getContent());
    }

    public function testHandleWhenControllerThrowsAnExceptionAndCatchIsTrueWithANonHandlingListener()
    {
        $exception = new \RuntimeException();

        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(KernelEvents::EXCEPTION, function ($event) {
            // should set a response, but does not
        });

        $kernel = new HttpKernel($dispatcher, $this->getResolver(function () use ($exception) { throw $exception; }));

        try {
            $kernel->handle(new Request(), HttpKernelInterface::MASTER_REQUEST, true);
            $this->fail('LogicException expected');
        } catch (\RuntimeException $e) {
            $this->assertSame($exception, $e);
        }
    }

    public function testHandleExceptionWithARedirectionResponse()
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(KernelEvents::EXCEPTION, function ($event) {
            $event->setResponse(new RedirectResponse('/login', 301));
        });

        $kernel = new HttpKernel($dispatcher, $this->getResolver(function () { throw new AccessDeniedHttpException(); }));
        $response = $kernel->handle(new Request());

        $this->assertEquals('301', $response->getStatusCode());
        $this->assertEquals('/login', $response->headers->get('Location'));
    }

    public function testHandleHttpException()
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(KernelEvents::EXCEPTION, function ($event) {
            $event->setResponse(new Response($event->getException()->getMessage()));
        });

        $kernel = new HttpKernel($dispatcher, $this->getResolver(function () { throw new MethodNotAllowedHttpException(array('POST')); }));
        $response = $kernel->handle(new Request());

        $this->assertEquals('405', $response->getStatusCode());
        $this->assertEquals('POST', $response->headers->get('Allow'));
    }

    /**
     * @dataProvider getStatusCodes
     */
    public function testHandleWhenAnExceptionIsHandledWithASpecificStatusCode($responseStatusCode, $expectedStatusCode)
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(KernelEvents::EXCEPTION, function ($event) use ($responseStatusCode, $expectedStatusCode) {
            $event->setResponse(new Response('', $responseStatusCode, array('X-Status-Code' => $expectedStatusCode)));
        });

        $kernel = new HttpKernel($dispatcher, $this->getResolver(function () { throw new \RuntimeException(); }));
        $response = $kernel->handle(new Request());

        $this->assertEquals($expectedStatusCode, $response->getStatusCode());
        $this->assertFalse($response->headers->has('X-Status-Code'));
    }

    public function getStatusCodes()
    {
        return array(
            array(200, 404),
            array(404, 200),
            array(301, 200),
            array(500, 200),
        );
    }

    public function testHandleWhenAListenerReturnsAResponse()
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(KernelEvents::REQUEST, function ($event) {
            $event->setResponse(new Response('hello'));
        });

        $kernel = new HttpKernel($dispatcher, $this->getResolver());

        $this->assertEquals('hello', $kernel->handle(new Request())->getContent());
    }

    /**
     * @expectedException \Symfony2\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function testHandleWhenNoControllerIsFound()
    {
        $dispatcher = new EventDispatcher();
        $kernel = new HttpKernel($dispatcher, $this->getResolver(false));

        $kernel->handle(new Request());
    }

    /**
     * @expectedException \LogicException
     */
    public function testHandleWhenTheControllerIsNotACallable()
    {
        $dispatcher = new EventDispatcher();
        $kernel = new HttpKernel($dispatcher, $this->getResolver('foobar'));

        $kernel->handle(new Request());
    }

    public function testHandleWhenTheControllerIsAClosure()
    {
        $response = new Response('foo');
        $dispatcher = new EventDispatcher();
        $kernel = new HttpKernel($dispatcher, $this->getResolver(function () use ($response) { return $response; }));

        $this->assertSame($response, $kernel->handle(new Request()));
    }

    public function testHandleWhenTheControllerIsAnObjectWithInvoke()
    {
        $dispatcher = new EventDispatcher();
        $kernel = new HttpKernel($dispatcher, $this->getResolver(new Controller()));

        $this->assertResponseEquals(new Response('foo'), $kernel->handle(new Request()));
    }

    public function testHandleWhenTheControllerIsAFunction()
    {
        $dispatcher = new EventDispatcher();
        $kernel = new HttpKernel($dispatcher, $this->getResolver('Symfony2\Component\HttpKernel\Tests\controller_func'));

        $this->assertResponseEquals(new Response('foo'), $kernel->handle(new Request()));
    }

    public function testHandleWhenTheControllerIsAnArray()
    {
        $dispatcher = new EventDispatcher();
        $kernel = new HttpKernel($dispatcher, $this->getResolver(array(new Controller(), 'controller')));

        $this->assertResponseEquals(new Response('foo'), $kernel->handle(new Request()));
    }

    public function testHandleWhenTheControllerIsAStaticArray()
    {
        $dispatcher = new EventDispatcher();
        $kernel = new HttpKernel($dispatcher, $this->getResolver(array('Symfony2\Component\HttpKernel\Tests\Controller', 'staticcontroller')));

        $this->assertResponseEquals(new Response('foo'), $kernel->handle(new Request()));
    }

    /**
     * @expectedException \LogicException
     */
    public function testHandleWhenTheControllerDoesNotReturnAResponse()
    {
        $dispatcher = new EventDispatcher();
        $kernel = new HttpKernel($dispatcher, $this->getResolver(function () { return 'foo'; }));

        $kernel->handle(new Request());
    }

    public function testHandleWhenTheControllerDoesNotReturnAResponseButAViewIsRegistered()
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(KernelEvents::VIEW, function ($event) {
            $event->setResponse(new Response($event->getControllerResult()));
        });
        $kernel = new HttpKernel($dispatcher, $this->getResolver(function () { return 'foo'; }));

        $this->assertEquals('foo', $kernel->handle(new Request())->getContent());
    }

    public function testHandleWithAResponseListener()
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(KernelEvents::RESPONSE, function ($event) {
            $event->setResponse(new Response('foo'));
        });
        $kernel = new HttpKernel($dispatcher, $this->getResolver());

        $this->assertEquals('foo', $kernel->handle(new Request())->getContent());
    }

    public function testTerminate()
    {
        $dispatcher = new EventDispatcher();
        $kernel = new HttpKernel($dispatcher, $this->getResolver());
        $dispatcher->addListener(KernelEvents::TERMINATE, function ($event) use (&$called, &$capturedKernel, &$capturedRequest, &$capturedResponse) {
            $called = true;
            $capturedKernel = $event->getKernel();
            $capturedRequest = $event->getRequest();
            $capturedResponse = $event->getResponse();
        });

        $kernel->terminate($request = Request::create('/'), $response = new Response());
        $this->assertTrue($called);
        $this->assertEquals($kernel, $capturedKernel);
        $this->assertEquals($request, $capturedRequest);
        $this->assertEquals($response, $capturedResponse);
    }

    public function testVerifyRequestStackPushPopDuringHandle()
    {
        $request = new Request();

        $stack = $this->getMockBuilder('Symfony2\Component\HttpFoundation\RequestStack')->setMethods(array('push', 'pop'))->getMock();
        $stack->expects($this->at(0))->method('push')->with($this->equalTo($request));
        $stack->expects($this->at(1))->method('pop');

        $dispatcher = new EventDispatcher();
        $kernel = new HttpKernel($dispatcher, $this->getResolver(), $stack);

        $kernel->handle($request, HttpKernelInterface::MASTER_REQUEST);
    }

    /**
     * @expectedException \Symfony2\Component\HttpKernel\Exception\BadRequestHttpException
     */
    public function testInconsistentClientIpsOnMasterRequests()
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(KernelEvents::REQUEST, function ($event) {
            $event->getRequest()->getClientIp();
        });

        $kernel = new HttpKernel($dispatcher, $this->getResolver());

        $request = new Request();
        $request->setTrustedProxies(array('1.1.1.1'));
        $request->server->set('REMOTE_ADDR', '1.1.1.1');
        $request->headers->set('FORWARDED', 'for=2.2.2.2');
        $request->headers->set('X_FORWARDED_FOR', '3.3.3.3');

        $kernel->handle($request, $kernel::MASTER_REQUEST, false);

        Request::setTrustedProxies(array());
    }

    protected function getResolver($controller = null)
    {
        if (null === $controller) {
            $controller = function () { return new Response('Hello'); };
        }

        $resolver = $this->getMockBuilder('Symfony2\\Component\\HttpKernel\\Controller\\ControllerResolverInterface')->getMock();
        $resolver->expects($this->any())
            ->method('getController')
            ->will($this->returnValue($controller));
        $resolver->expects($this->any())
            ->method('getArguments')
            ->will($this->returnValue(array()));

        return $resolver;
    }

    protected function assertResponseEquals(Response $expected, Response $actual)
    {
        $expected->setDate($actual->getDate());
        $this->assertEquals($expected, $actual);
    }
}

class Controller
{
    public function __invoke()
    {
        return new Response('foo');
    }

    public function controller()
    {
        return new Response('foo');
    }

    public static function staticController()
    {
        return new Response('foo');
    }
}

function controller_func()
{
    return new Response('foo');
}
