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

use Symfony2\Component\EventDispatcher\EventDispatcher;
use Symfony2\Component\HttpFoundation\Request;
use Symfony2\Component\HttpFoundation\Response;
use Symfony2\Component\HttpKernel\Controller\ControllerResolverInterface;
use Symfony2\Component\HttpKernel\HttpKernel;

class TestHttpKernel extends HttpKernel implements ControllerResolverInterface
{
    public function __construct()
    {
        parent::__construct(new EventDispatcher(), $this);
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
        return new Response('Request: '.$request->getRequestUri());
    }
}
