<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\HttpKernel\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\HttpFoundation\Request;
use Symfony2\Component\HttpFoundation\Response;
use Symfony2\Component\HttpKernel\DependencyInjection\LazyLoadingFragmentHandler;

class LazyLoadingFragmentHandlerTest extends TestCase
{
    public function test()
    {
        $renderer = $this->getMockBuilder('Symfony2\Component\HttpKernel\Fragment\FragmentRendererInterface')->getMock();
        $renderer->expects($this->once())->method('getName')->will($this->returnValue('foo'));
        $renderer->expects($this->any())->method('render')->will($this->returnValue(new Response()));

        $requestStack = $this->getMockBuilder('Symfony2\Component\HttpFoundation\RequestStack')->getMock();
        $requestStack->expects($this->any())->method('getCurrentRequest')->will($this->returnValue(Request::create('/')));

        $container = $this->getMockBuilder('Symfony2\Component\DependencyInjection\ContainerInterface')->getMock();
        $container->expects($this->once())->method('get')->will($this->returnValue($renderer));

        $handler = new LazyLoadingFragmentHandler($container, $requestStack, false);
        $handler->addRendererService('foo', 'foo');

        $handler->render('/foo', 'foo');

        // second call should not lazy-load anymore (see once() above on the get() method)
        $handler->render('/foo', 'foo');
    }
}
