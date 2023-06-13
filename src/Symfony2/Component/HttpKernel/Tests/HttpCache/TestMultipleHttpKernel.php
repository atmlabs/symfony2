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

class TestMultipleHttpKernel extends HttpKernel implements ControllerResolverInterface
{
    protected $bodies = array();
    protected $statuses = array();
    protected $headers = array();
    protected $called = false;
    protected $backendRequest;

    public function __construct($responses)
    {
        foreach ($responses as $response) {
            $this->bodies[] = $response['body'];
            $this->statuses[] = $response['status'];
            $this->headers[] = $response['headers'];
        }

        parent::__construct(new EventDispatcher(), $this);
    }

    public function getBackendRequest()
    {
        return $this->backendRequest;
    }

    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = false)
    {
        $this->backendRequest = $request;

        return parent::handle($request, $type, $catch);
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

        $response = new Response(array_shift($this->bodies), array_shift($this->statuses), array_shift($this->headers));

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
