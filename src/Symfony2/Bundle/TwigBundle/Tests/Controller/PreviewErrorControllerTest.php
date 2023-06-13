<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\TwigBundle\Tests\Controller;

use Symfony2\Bundle\TwigBundle\Controller\PreviewErrorController;
use Symfony2\Bundle\TwigBundle\Tests\TestCase;
use Symfony2\Component\HttpFoundation\Request;
use Symfony2\Component\HttpFoundation\Response;
use Symfony2\Component\HttpKernel\HttpKernelInterface;

class PreviewErrorControllerTest extends TestCase
{
    public function testForwardRequestToConfiguredController()
    {
        $self = $this;

        $request = Request::create('whatever');
        $response = new Response('');
        $code = 123;
        $logicalControllerName = 'foo:bar:baz';

        $kernel = $this->getMockBuilder('\Symfony\Component\HttpKernel\HttpKernelInterface')->getMock();
        $kernel
            ->expects($this->once())
            ->method('handle')
            ->with(
                $this->callback(function (Request $request) use ($self, $logicalControllerName, $code) {
                    $self->assertEquals($logicalControllerName, $request->attributes->get('_controller'));

                    $exception = $request->attributes->get('exception');
                    $self->assertInstanceOf('Symfony\Component\Debug\Exception\FlattenException', $exception);
                    $self->assertEquals($code, $exception->getStatusCode());

                    $self->assertFalse($request->attributes->get('showException'));

                    return true;
                }),
                $this->equalTo(HttpKernelInterface::SUB_REQUEST)
            )
            ->will($this->returnValue($response));

        $controller = new PreviewErrorController($kernel, $logicalControllerName);

        $this->assertSame($response, $controller->previewErrorPageAction($request, $code));
    }
}
