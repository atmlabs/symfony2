<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\HttpKernel\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\EventDispatcher\EventDispatcher;
use Symfony2\Component\HttpFoundation\Request;
use Symfony2\Component\HttpFoundation\Response;
use Symfony2\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony2\Component\HttpKernel\EventListener\SurrogateListener;
use Symfony2\Component\HttpKernel\HttpCache\Esi;
use Symfony2\Component\HttpKernel\HttpKernelInterface;
use Symfony2\Component\HttpKernel\KernelEvents;

class SurrogateListenerTest extends TestCase
{
    public function testFilterDoesNothingForSubRequests()
    {
        $dispatcher = new EventDispatcher();
        $kernel = $this->getMockBuilder('Symfony2\Component\HttpKernel\HttpKernelInterface')->getMock();
        $response = new Response('foo <esi:include src="" />');
        $listener = new SurrogateListener(new Esi());

        $dispatcher->addListener(KernelEvents::RESPONSE, array($listener, 'onKernelResponse'));
        $event = new FilterResponseEvent($kernel, new Request(), HttpKernelInterface::SUB_REQUEST, $response);
        $dispatcher->dispatch(KernelEvents::RESPONSE, $event);

        $this->assertEquals('', $event->getResponse()->headers->get('Surrogate-Control'));
    }

    public function testFilterWhenThereIsSomeEsiIncludes()
    {
        $dispatcher = new EventDispatcher();
        $kernel = $this->getMockBuilder('Symfony2\Component\HttpKernel\HttpKernelInterface')->getMock();
        $response = new Response('foo <esi:include src="" />');
        $listener = new SurrogateListener(new Esi());

        $dispatcher->addListener(KernelEvents::RESPONSE, array($listener, 'onKernelResponse'));
        $event = new FilterResponseEvent($kernel, new Request(), HttpKernelInterface::MASTER_REQUEST, $response);
        $dispatcher->dispatch(KernelEvents::RESPONSE, $event);

        $this->assertEquals('content="ESI/1.0"', $event->getResponse()->headers->get('Surrogate-Control'));
    }

    public function testFilterWhenThereIsNoEsiIncludes()
    {
        $dispatcher = new EventDispatcher();
        $kernel = $this->getMockBuilder('Symfony2\Component\HttpKernel\HttpKernelInterface')->getMock();
        $response = new Response('foo');
        $listener = new SurrogateListener(new Esi());

        $dispatcher->addListener(KernelEvents::RESPONSE, array($listener, 'onKernelResponse'));
        $event = new FilterResponseEvent($kernel, new Request(), HttpKernelInterface::MASTER_REQUEST, $response);
        $dispatcher->dispatch(KernelEvents::RESPONSE, $event);

        $this->assertEquals('', $event->getResponse()->headers->get('Surrogate-Control'));
    }
}
