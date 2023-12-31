<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\HttpKernel\Tests\HttpCache;

use Symfony2\Component\EventDispatcher\EventDispatcher;
use Symfony2\Component\HttpFoundation\Request;
use Symfony2\Component\HttpFoundation\Response;
use Symfony2\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony2\Component\HttpKernel\HttpKernel;
use Symfony2\Component\HttpKernel\HttpKernelInterface;

class TestHttpKernel extends HttpKernel implements ControllerResolverInterface
{
    protected $body;
    protected $status;
    protected $headers;
    protected $called = false;
    protected $customizer;
    protected $catch = false;
    protected $backendRequest;

    public function __construct($body, $status, $headers, \Closure $customizer = null)
    {
        $this->body = $body;
        $this->status = $status;
        $this->headers = $headers;
        $this->customizer = $customizer;
        $this->trustedHeadersReflector = new \ReflectionProperty('Symfony2\Component\HttpFoundation\Request', 'trustedHeaders');
        $this->trustedHeadersReflector->setAccessible(true);

        parent::__construct(new EventDispatcher(), $this);
    }

    public function assert(\Closure $callback)
    {
        $trustedConfig = array(Request::getTrustedProxies(), $this->trustedHeadersReflector->getValue());

        list($trustedProxies, $trustedHeaders, $backendRequest) = $this->backendRequest;
        Request::setTrustedProxies($trustedProxies);
        $this->trustedHeadersReflector->setValue(null, $trustedHeaders);

        try {
            $e = null;
            $callback($backendRequest);
        } catch (\Throwable $e) {
        } catch (\Exception $e) {
        }

        list($trustedProxies, $trustedHeaders) = $trustedConfig;
        Request::setTrustedProxies($trustedProxies);
        $this->trustedHeadersReflector->setValue(null, $trustedHeaders);

        if (null !== $e) {
            throw $e;
        }
    }

    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = false)
    {
        $this->catch = $catch;
        $this->backendRequest = array($request::getTrustedProxies(), $this->trustedHeadersReflector->getValue(), $request);

        return parent::handle($request, $type, $catch);
    }

    public function isCatchingExceptions()
    {
        return $this->catch;
    }

    public function getController(Request $request)
    {
        return array($this, 'callController');
    }

    public function getArguments(Request $request, $controller)
    {
        return array($request);
    }

    public function callController(Request $request)
    {
        $this->called = true;

        $response = new Response($this->body, $this->status, $this->headers);

        if (null !== $customizer = $this->customizer) {
            $customizer($request, $response);
        }

        return $response;
    }

    public function hasBeenCalled()
    {
        return $this->called;
    }

    public function reset()
    {
        $this->called = false;
    }
}
