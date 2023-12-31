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
use Symfony2\Component\HttpFoundation\RequestStack;
use Symfony2\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony2\Component\HttpKernel\Event\GetResponseEvent;
use Symfony2\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony2\Component\HttpKernel\Event\PostResponseEvent;
use Symfony2\Component\HttpKernel\EventListener\ProfilerListener;
use Symfony2\Component\HttpKernel\Exception\HttpException;
use Symfony2\Component\HttpKernel\Kernel;

class ProfilerListenerTest extends TestCase
{
    /**
     * Test to ensure BC without RequestStack.
     *
     * @group legacy
     */
    public function testLegacyEventsWithoutRequestStack()
    {
        $profile = $this->getMockBuilder('Symfony2\Component\HttpKernel\Profiler\Profile')
            ->disableOriginalConstructor()
            ->getMock();

        $profiler = $this->getMockBuilder('Symfony2\Component\HttpKernel\Profiler\Profiler')
            ->disableOriginalConstructor()
            ->getMock();
        $profiler->expects($this->once())
            ->method('collect')
            ->will($this->returnValue($profile));

        $kernel = $this->getMockBuilder('Symfony2\Component\HttpKernel\HttpKernelInterface')->getMock();

        $request = $this->getMockBuilder('Symfony2\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('Symfony2\Component\HttpFoundation\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $listener = new ProfilerListener($profiler);
        $listener->onKernelRequest(new GetResponseEvent($kernel, $request, Kernel::MASTER_REQUEST));
        $listener->onKernelResponse(new FilterResponseEvent($kernel, $request, Kernel::MASTER_REQUEST, $response));
        $listener->onKernelTerminate(new PostResponseEvent($kernel, $request, $response));
    }

    /**
     * Test a master and sub request with an exception and `onlyException` profiler option enabled.
     */
    public function testKernelTerminate()
    {
        $profile = $this->getMockBuilder('Symfony2\Component\HttpKernel\Profiler\Profile')
            ->disableOriginalConstructor()
            ->getMock();

        $profiler = $this->getMockBuilder('Symfony2\Component\HttpKernel\Profiler\Profiler')
            ->disableOriginalConstructor()
            ->getMock();

        $profiler->expects($this->once())
            ->method('collect')
            ->will($this->returnValue($profile));

        $kernel = $this->getMockBuilder('Symfony2\Component\HttpKernel\HttpKernelInterface')->getMock();

        $masterRequest = $this->getMockBuilder('Symfony2\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $subRequest = $this->getMockBuilder('Symfony2\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder('Symfony2\Component\HttpFoundation\Response')
            ->disableOriginalConstructor()
            ->getMock();

        $requestStack = new RequestStack();
        $requestStack->push($masterRequest);

        $onlyException = true;
        $listener = new ProfilerListener($profiler, $requestStack, null, $onlyException);

        // master request
        $listener->onKernelResponse(new FilterResponseEvent($kernel, $masterRequest, Kernel::MASTER_REQUEST, $response));

        // sub request
        $listener->onKernelException(new GetResponseForExceptionEvent($kernel, $subRequest, Kernel::SUB_REQUEST, new HttpException(404)));
        $listener->onKernelResponse(new FilterResponseEvent($kernel, $subRequest, Kernel::SUB_REQUEST, $response));

        $listener->onKernelTerminate(new PostResponseEvent($kernel, $masterRequest, $response));
    }
}
