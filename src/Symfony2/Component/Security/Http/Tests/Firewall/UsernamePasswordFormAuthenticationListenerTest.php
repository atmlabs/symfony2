<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Security\Tests\Http\Firewall;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\HttpFoundation\Request;
use Symfony2\Component\HttpFoundation\Response;
use Symfony2\Component\HttpKernel\Event\GetResponseEvent;
use Symfony2\Component\HttpKernel\HttpKernelInterface;
use Symfony2\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony2\Component\Security\Core\SecurityContextInterface;
use Symfony2\Component\Security\Http\Authentication\DefaultAuthenticationFailureHandler;
use Symfony2\Component\Security\Http\Authentication\DefaultAuthenticationSuccessHandler;
use Symfony2\Component\Security\Http\Firewall\UsernamePasswordFormAuthenticationListener;
use Symfony2\Component\Security\Http\HttpUtils;
use Symfony2\Component\Security\Http\Session\SessionAuthenticationStrategy;

class UsernamePasswordFormAuthenticationListenerTest extends TestCase
{
    /**
     * @dataProvider getUsernameForLength
     */
    public function testHandleWhenUsernameLength($username, $ok)
    {
        $request = Request::create('/login_check', 'POST', array('_username' => $username));
        $request->setSession($this->getMockBuilder('Symfony2\Component\HttpFoundation\Session\SessionInterface')->getMock());

        $httpUtils = $this->getMockBuilder('Symfony2\Component\Security\Http\HttpUtils')->getMock();
        $httpUtils
            ->expects($this->any())
            ->method('checkRequestPath')
            ->will($this->returnValue(true))
        ;

        $failureHandler = $this->getMockBuilder('Symfony2\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface')->getMock();
        $failureHandler
            ->expects($ok ? $this->never() : $this->once())
            ->method('onAuthenticationFailure')
            ->will($this->returnValue(new Response()))
        ;

        $authenticationManager = $this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\AuthenticationProviderManager')->disableOriginalConstructor()->getMock();
        $authenticationManager
            ->expects($ok ? $this->once() : $this->never())
            ->method('authenticate')
            ->will($this->returnValue(new Response()))
        ;

        $listener = new UsernamePasswordFormAuthenticationListener(
            $this->getMockBuilder('Symfony2\Component\Security\Core\SecurityContextInterface')->getMock(),
            $authenticationManager,
            $this->getMockBuilder('Symfony2\Component\Security\Http\Session\SessionAuthenticationStrategyInterface')->getMock(),
            $httpUtils,
            'TheProviderKey',
            $this->getMockBuilder('Symfony2\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface')->getMock(),
            $failureHandler,
            array('require_previous_session' => false)
        );

        $event = $this->getMockBuilder('Symfony2\Component\HttpKernel\Event\GetResponseEvent')->disableOriginalConstructor()->getMock();
        $event
            ->expects($this->any())
            ->method('getRequest')
            ->will($this->returnValue($request))
        ;

        $listener->handle($event);
    }

    /**
     * @dataProvider postOnlyDataProvider
     * @expectedException \Symfony2\Component\HttpKernel\Exception\BadRequestHttpException
     * @expectedExceptionMessage The key "_username" must be a string, "array" given.
     */
    public function testHandleNonStringUsername($postOnly)
    {
        $request = Request::create('/login_check', 'POST', array('_username' => array()));
        $request->setSession($this->getMockBuilder('Symfony2\Component\HttpFoundation\Session\SessionInterface')->getMock());
        $listener = new UsernamePasswordFormAuthenticationListener(
            new TokenStorage(),
            $this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\AuthenticationManagerInterface')->getMock(),
            new SessionAuthenticationStrategy(SessionAuthenticationStrategy::NONE),
            $httpUtils = new HttpUtils(),
            'foo',
            new DefaultAuthenticationSuccessHandler($httpUtils),
            new DefaultAuthenticationFailureHandler($this->getMockBuilder('Symfony2\Component\HttpKernel\HttpKernelInterface')->getMock(), $httpUtils),
            array('require_previous_session' => false, 'post_only' => $postOnly)
        );
        $event = new GetResponseEvent($this->getMockBuilder('Symfony2\Component\HttpKernel\HttpKernelInterface')->getMock(), $request, HttpKernelInterface::MASTER_REQUEST);
        $listener->handle($event);
    }

    public function postOnlyDataProvider()
    {
        return array(
            array(true),
            array(false),
        );
    }

    public function getUsernameForLength()
    {
        return array(
            array(str_repeat('x', SecurityContextInterface::MAX_USERNAME_LENGTH + 1), false),
            array(str_repeat('x', SecurityContextInterface::MAX_USERNAME_LENGTH - 1), true),
        );
    }
}
