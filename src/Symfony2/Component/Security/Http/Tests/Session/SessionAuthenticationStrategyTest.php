<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Security\Http\Tests\Session;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\Security\Http\Session\SessionAuthenticationStrategy;

class SessionAuthenticationStrategyTest extends TestCase
{
    public function testSessionIsNotChanged()
    {
        $request = $this->getRequest();
        $request->expects($this->never())->method('getSession');

        $strategy = new SessionAuthenticationStrategy(SessionAuthenticationStrategy::NONE);
        $strategy->onAuthentication($request, $this->getToken());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Invalid session authentication strategy "foo"
     */
    public function testUnsupportedStrategy()
    {
        $request = $this->getRequest();
        $request->expects($this->never())->method('getSession');

        $strategy = new SessionAuthenticationStrategy('foo');
        $strategy->onAuthentication($request, $this->getToken());
    }

    public function testSessionIsMigrated()
    {
        if (\PHP_VERSION_ID >= 50400 && \PHP_VERSION_ID < 50411) {
            $this->markTestSkipped('We cannot destroy the old session on PHP 5.4.0 - 5.4.10.');
        }

        $session = $this->getMockBuilder('Symfony2\Component\HttpFoundation\Session\SessionInterface')->getMock();
        $session->expects($this->once())->method('migrate')->with($this->equalTo(true));

        $strategy = new SessionAuthenticationStrategy(SessionAuthenticationStrategy::MIGRATE);
        $strategy->onAuthentication($this->getRequest($session), $this->getToken());
    }

    public function testSessionIsMigratedWithPhp54Workaround()
    {
        if (\PHP_VERSION_ID < 50400 || \PHP_VERSION_ID >= 50411) {
            $this->markTestSkipped('This PHP version is not affected.');
        }

        $session = $this->getMockBuilder('Symfony2\Component\HttpFoundation\Session\SessionInterface')->getMock();
        $session->expects($this->once())->method('migrate')->with($this->equalTo(false));

        $strategy = new SessionAuthenticationStrategy(SessionAuthenticationStrategy::MIGRATE);
        $strategy->onAuthentication($this->getRequest($session), $this->getToken());
    }

    public function testSessionIsInvalidated()
    {
        $session = $this->getMockBuilder('Symfony2\Component\HttpFoundation\Session\SessionInterface')->getMock();
        $session->expects($this->once())->method('invalidate');

        $strategy = new SessionAuthenticationStrategy(SessionAuthenticationStrategy::INVALIDATE);
        $strategy->onAuthentication($this->getRequest($session), $this->getToken());
    }

    private function getRequest($session = null)
    {
        $request = $this->getMockBuilder('Symfony2\Component\HttpFoundation\Request')->getMock();

        if (null !== $session) {
            $request->expects($this->any())->method('getSession')->will($this->returnValue($session));
        }

        return $request;
    }

    private function getToken()
    {
        return $this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\Token\TokenInterface')->getMock();
    }
}
