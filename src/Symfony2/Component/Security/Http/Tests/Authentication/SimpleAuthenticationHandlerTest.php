<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Security\Http\Tests;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\HttpFoundation\Response;
use Symfony2\Component\Security\Core\Authentication\SimpleAuthenticatorInterface;
use Symfony2\Component\Security\Core\Exception\AuthenticationException;
use Symfony2\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony2\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony2\Component\Security\Http\Authentication\SimpleAuthenticationHandler;

class SimpleAuthenticationHandlerTest extends TestCase
{
    private $successHandler;

    private $failureHandler;

    private $request;

    private $token;

    private $authenticationException;

    private $response;

    protected function setUp()
    {
        $this->successHandler = $this->getMockBuilder('Symfony2\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface')->getMock();
        $this->failureHandler = $this->getMockBuilder('Symfony2\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface')->getMock();

        $this->request = $this->getMockBuilder('Symfony2\Component\HttpFoundation\Request')->getMock();
        $this->token = $this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\Token\TokenInterface')->getMock();
        // No methods are invoked on the exception; we just assert on its class
        $this->authenticationException = new AuthenticationException();

        $this->response = new Response();
    }

    public function testOnAuthenticationSuccessFallsBackToDefaultHandlerIfSimpleIsNotASuccessHandler()
    {
        $authenticator = $this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\SimpleAuthenticatorInterface')->getMock();

        $this->successHandler->expects($this->once())
            ->method('onAuthenticationSuccess')
            ->with($this->request, $this->token)
            ->will($this->returnValue($this->response));

        $handler = new SimpleAuthenticationHandler($authenticator, $this->successHandler, $this->failureHandler);
        $result = $handler->onAuthenticationSuccess($this->request, $this->token);

        $this->assertSame($this->response, $result);
    }

    public function testOnAuthenticationSuccessCallsSimpleAuthenticator()
    {
        $this->successHandler->expects($this->never())
            ->method('onAuthenticationSuccess');

        $authenticator = $this->getMockForAbstractClass('Symfony2\Component\Security\Http\Tests\TestSuccessHandlerInterface');
        $authenticator->expects($this->once())
            ->method('onAuthenticationSuccess')
            ->with($this->request, $this->token)
            ->will($this->returnValue($this->response));

        $handler = new SimpleAuthenticationHandler($authenticator, $this->successHandler, $this->failureHandler);
        $result = $handler->onAuthenticationSuccess($this->request, $this->token);

        $this->assertSame($this->response, $result);
    }

    /**
     * @expectedException        \UnexpectedValueException
     * @expectedExceptionMessage onAuthenticationSuccess method must return null to use the default success handler, or a Response object
     */
    public function testOnAuthenticationSuccessThrowsAnExceptionIfNonResponseIsReturned()
    {
        $this->successHandler->expects($this->never())
            ->method('onAuthenticationSuccess');

        $authenticator = $this->getMockForAbstractClass('Symfony2\Component\Security\Http\Tests\TestSuccessHandlerInterface');
        $authenticator->expects($this->once())
            ->method('onAuthenticationSuccess')
            ->with($this->request, $this->token)
            ->will($this->returnValue(new \stdClass()));

        $handler = new SimpleAuthenticationHandler($authenticator, $this->successHandler, $this->failureHandler);
        $handler->onAuthenticationSuccess($this->request, $this->token);
    }

    public function testOnAuthenticationSuccessFallsBackToDefaultHandlerIfNullIsReturned()
    {
        $this->successHandler->expects($this->once())
            ->method('onAuthenticationSuccess')
            ->with($this->request, $this->token)
            ->will($this->returnValue($this->response));

        $authenticator = $this->getMockForAbstractClass('Symfony2\Component\Security\Http\Tests\TestSuccessHandlerInterface');
        $authenticator->expects($this->once())
            ->method('onAuthenticationSuccess')
            ->with($this->request, $this->token)
            ->will($this->returnValue(null));

        $handler = new SimpleAuthenticationHandler($authenticator, $this->successHandler, $this->failureHandler);
        $result = $handler->onAuthenticationSuccess($this->request, $this->token);

        $this->assertSame($this->response, $result);
    }

    public function testOnAuthenticationFailureFallsBackToDefaultHandlerIfSimpleIsNotAFailureHandler()
    {
        $authenticator = $this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\SimpleAuthenticatorInterface')->getMock();

        $this->failureHandler->expects($this->once())
            ->method('onAuthenticationFailure')
            ->with($this->request, $this->authenticationException)
            ->will($this->returnValue($this->response));

        $handler = new SimpleAuthenticationHandler($authenticator, $this->successHandler, $this->failureHandler);
        $result = $handler->onAuthenticationFailure($this->request, $this->authenticationException);

        $this->assertSame($this->response, $result);
    }

    public function testOnAuthenticationFailureCallsSimpleAuthenticator()
    {
        $this->failureHandler->expects($this->never())
            ->method('onAuthenticationFailure');

        $authenticator = $this->getMockForAbstractClass('Symfony2\Component\Security\Http\Tests\TestFailureHandlerInterface');
        $authenticator->expects($this->once())
            ->method('onAuthenticationFailure')
            ->with($this->request, $this->authenticationException)
            ->will($this->returnValue($this->response));

        $handler = new SimpleAuthenticationHandler($authenticator, $this->successHandler, $this->failureHandler);
        $result = $handler->onAuthenticationFailure($this->request, $this->authenticationException);

        $this->assertSame($this->response, $result);
    }

    /**
     * @expectedException        \UnexpectedValueException
     * @expectedExceptionMessage onAuthenticationFailure method must return null to use the default failure handler, or a Response object
     */
    public function testOnAuthenticationFailureThrowsAnExceptionIfNonResponseIsReturned()
    {
        $this->failureHandler->expects($this->never())
            ->method('onAuthenticationFailure');

        $authenticator = $this->getMockForAbstractClass('Symfony2\Component\Security\Http\Tests\TestFailureHandlerInterface');
        $authenticator->expects($this->once())
            ->method('onAuthenticationFailure')
            ->with($this->request, $this->authenticationException)
            ->will($this->returnValue(new \stdClass()));

        $handler = new SimpleAuthenticationHandler($authenticator, $this->successHandler, $this->failureHandler);
        $handler->onAuthenticationFailure($this->request, $this->authenticationException);
    }

    public function testOnAuthenticationFailureFallsBackToDefaultHandlerIfNullIsReturned()
    {
        $this->failureHandler->expects($this->once())
            ->method('onAuthenticationFailure')
            ->with($this->request, $this->authenticationException)
            ->will($this->returnValue($this->response));

        $authenticator = $this->getMockForAbstractClass('Symfony2\Component\Security\Http\Tests\TestFailureHandlerInterface');
        $authenticator->expects($this->once())
            ->method('onAuthenticationFailure')
            ->with($this->request, $this->authenticationException)
            ->will($this->returnValue(null));

        $handler = new SimpleAuthenticationHandler($authenticator, $this->successHandler, $this->failureHandler);
        $result = $handler->onAuthenticationFailure($this->request, $this->authenticationException);

        $this->assertSame($this->response, $result);
    }
}

interface TestSuccessHandlerInterface extends AuthenticationSuccessHandlerInterface, SimpleAuthenticatorInterface
{
}

interface TestFailureHandlerInterface extends AuthenticationFailureHandlerInterface, SimpleAuthenticatorInterface
{
}
