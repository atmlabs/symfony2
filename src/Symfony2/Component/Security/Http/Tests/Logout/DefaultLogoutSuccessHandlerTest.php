<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Security\Http\Tests\Logout;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\HttpFoundation\Response;
use Symfony2\Component\Security\Http\Logout\DefaultLogoutSuccessHandler;

class DefaultLogoutSuccessHandlerTest extends TestCase
{
    public function testLogout()
    {
        $request = $this->getMockBuilder('Symfony2\Component\HttpFoundation\Request')->getMock();
        $response = new Response();

        $httpUtils = $this->getMockBuilder('Symfony2\Component\Security\Http\HttpUtils')->getMock();
        $httpUtils->expects($this->once())
            ->method('createRedirectResponse')
            ->with($request, '/dashboard')
            ->will($this->returnValue($response));

        $handler = new DefaultLogoutSuccessHandler($httpUtils, '/dashboard');
        $result = $handler->onLogoutSuccess($request);

        $this->assertSame($response, $result);
    }
}
