<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Security\Http\Tests\RememberMe;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\HttpFoundation\Request;
use Symfony2\Component\HttpFoundation\Response;
use Symfony2\Component\HttpFoundation\ResponseHeaderBag;
use Symfony2\Component\Security\Core\Authentication\RememberMe\PersistentToken;
use Symfony2\Component\Security\Core\Exception\CookieTheftException;
use Symfony2\Component\Security\Core\Exception\TokenNotFoundException;
use Symfony2\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony2\Component\Security\Http\RememberMe\PersistentTokenBasedRememberMeServices;
use Symfony2\Component\Security\Http\RememberMe\RememberMeServicesInterface;

class PersistentTokenBasedRememberMeServicesTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        try {
            random_bytes(1);
        } catch (\Exception $e) {
            self::markTestSkipped($e->getMessage());
        }
    }

    public function testAutoLoginReturnsNullWhenNoCookie()
    {
        $service = $this->getService(null, array('name' => 'foo'));

        $this->assertNull($service->autoLogin(new Request()));
    }

    public function testAutoLoginThrowsExceptionOnInvalidCookie()
    {
        $service = $this->getService(null, array('name' => 'foo', 'path' => null, 'domain' => null, 'always_remember_me' => false, 'remember_me_parameter' => 'foo'));
        $request = new Request();
        $request->request->set('foo', 'true');
        $request->cookies->set('foo', 'foo');

        $this->assertNull($service->autoLogin($request));
        $this->assertTrue($request->attributes->get(RememberMeServicesInterface::COOKIE_ATTR_NAME)->isCleared());
    }

    public function testAutoLoginThrowsExceptionOnNonExistentToken()
    {
        $service = $this->getService(null, array('name' => 'foo', 'path' => null, 'domain' => null, 'always_remember_me' => false, 'remember_me_parameter' => 'foo'));
        $request = new Request();
        $request->request->set('foo', 'true');
        $request->cookies->set('foo', $this->encodeCookie(array(
            $series = 'fooseries',
            $tokenValue = 'foovalue',
        )));

        $tokenProvider = $this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\RememberMe\TokenProviderInterface')->getMock();
        $tokenProvider
            ->expects($this->once())
            ->method('loadTokenBySeries')
            ->will($this->throwException(new TokenNotFoundException('Token not found.')))
        ;
        $service->setTokenProvider($tokenProvider);

        $this->assertNull($service->autoLogin($request));
        $this->assertTrue($request->attributes->get(RememberMeServicesInterface::COOKIE_ATTR_NAME)->isCleared());
    }

    public function testAutoLoginReturnsNullOnNonExistentUser()
    {
        $userProvider = $this->getProvider();
        $service = $this->getService($userProvider, array('name' => 'foo', 'path' => null, 'domain' => null, 'always_remember_me' => true, 'lifetime' => 3600, 'secure' => false, 'httponly' => false));
        $request = new Request();
        $request->cookies->set('foo', $this->encodeCookie(array('fooseries', 'foovalue')));

        $tokenProvider = $this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\RememberMe\TokenProviderInterface')->getMock();
        $tokenProvider
            ->expects($this->once())
            ->method('loadTokenBySeries')
            ->will($this->returnValue(new PersistentToken('fooclass', 'fooname', 'fooseries', 'foovalue', new \DateTime())))
        ;
        $service->setTokenProvider($tokenProvider);

        $userProvider
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->will($this->throwException(new UsernameNotFoundException('user not found')))
        ;

        $this->assertNull($service->autoLogin($request));
        $this->assertTrue($request->attributes->has(RememberMeServicesInterface::COOKIE_ATTR_NAME));
    }

    public function testAutoLoginThrowsExceptionOnStolenCookieAndRemovesItFromThePersistentBackend()
    {
        $userProvider = $this->getProvider();
        $service = $this->getService($userProvider, array('name' => 'foo', 'path' => null, 'domain' => null, 'always_remember_me' => true));
        $request = new Request();
        $request->cookies->set('foo', $this->encodeCookie(array('fooseries', 'foovalue')));

        $tokenProvider = $this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\RememberMe\TokenProviderInterface')->getMock();
        $service->setTokenProvider($tokenProvider);

        $tokenProvider
            ->expects($this->once())
            ->method('loadTokenBySeries')
            ->will($this->returnValue(new PersistentToken('fooclass', 'foouser', 'fooseries', 'anotherFooValue', new \DateTime())))
        ;

        $tokenProvider
            ->expects($this->once())
            ->method('deleteTokenBySeries')
            ->with($this->equalTo('fooseries'))
            ->will($this->returnValue(null))
        ;

        try {
            $service->autoLogin($request);
            $this->fail('Expected CookieTheftException was not thrown.');
        } catch (CookieTheftException $e) {
        }

        $this->assertTrue($request->attributes->has(RememberMeServicesInterface::COOKIE_ATTR_NAME));
    }

    public function testAutoLoginDoesNotAcceptAnExpiredCookie()
    {
        $service = $this->getService(null, array('name' => 'foo', 'path' => null, 'domain' => null, 'always_remember_me' => true, 'lifetime' => 3600));
        $request = new Request();
        $request->cookies->set('foo', $this->encodeCookie(array('fooseries', 'foovalue')));

        $tokenProvider = $this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\RememberMe\TokenProviderInterface')->getMock();
        $tokenProvider
            ->expects($this->once())
            ->method('loadTokenBySeries')
            ->with($this->equalTo('fooseries'))
            ->will($this->returnValue(new PersistentToken('fooclass', 'username', 'fooseries', 'foovalue', new \DateTime('yesterday'))))
        ;
        $service->setTokenProvider($tokenProvider);

        $this->assertNull($service->autoLogin($request));
        $this->assertTrue($request->attributes->has(RememberMeServicesInterface::COOKIE_ATTR_NAME));
    }

    public function testAutoLogin()
    {
        $user = $this->getMockBuilder('Symfony2\Component\Security\Core\User\UserInterface')->getMock();
        $user
            ->expects($this->once())
            ->method('getRoles')
            ->will($this->returnValue(array('ROLE_FOO')))
        ;

        $userProvider = $this->getProvider();
        $userProvider
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->with($this->equalTo('foouser'))
            ->will($this->returnValue($user))
        ;

        $service = $this->getService($userProvider, array('name' => 'foo', 'path' => null, 'domain' => null, 'secure' => false, 'httponly' => false, 'always_remember_me' => true, 'lifetime' => 3600));
        $request = new Request();
        $request->cookies->set('foo', $this->encodeCookie(array('fooseries', 'foovalue')));

        $tokenProvider = $this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\RememberMe\TokenProviderInterface')->getMock();
        $tokenProvider
            ->expects($this->once())
            ->method('loadTokenBySeries')
            ->with($this->equalTo('fooseries'))
            ->will($this->returnValue(new PersistentToken('fooclass', 'foouser', 'fooseries', 'foovalue', new \DateTime())))
        ;
        $service->setTokenProvider($tokenProvider);

        $returnedToken = $service->autoLogin($request);

        $this->assertInstanceOf('Symfony2\Component\Security\Core\Authentication\Token\RememberMeToken', $returnedToken);
        $this->assertSame($user, $returnedToken->getUser());
        $this->assertEquals('foosecret', $returnedToken->getSecret());
        $this->assertTrue($request->attributes->has(RememberMeServicesInterface::COOKIE_ATTR_NAME));
    }

    public function testLogout()
    {
        $service = $this->getService(null, array('name' => 'foo', 'path' => '/foo', 'domain' => 'foodomain.foo', 'secure' => true, 'httponly' => false));
        $request = new Request();
        $request->cookies->set('foo', $this->encodeCookie(array('fooseries', 'foovalue')));
        $response = new Response();
        $token = $this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\Token\TokenInterface')->getMock();

        $tokenProvider = $this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\RememberMe\TokenProviderInterface')->getMock();
        $tokenProvider
            ->expects($this->once())
            ->method('deleteTokenBySeries')
            ->with($this->equalTo('fooseries'))
            ->will($this->returnValue(null))
        ;
        $service->setTokenProvider($tokenProvider);

        $service->logout($request, $response, $token);

        $cookie = $request->attributes->get(RememberMeServicesInterface::COOKIE_ATTR_NAME);
        $this->assertTrue($cookie->isCleared());
        $this->assertEquals('/foo', $cookie->getPath());
        $this->assertEquals('foodomain.foo', $cookie->getDomain());
        $this->assertTrue($cookie->isSecure());
        $this->assertFalse($cookie->isHttpOnly());
    }

    public function testLogoutSimplyIgnoresNonSetRequestCookie()
    {
        $service = $this->getService(null, array('name' => 'foo', 'path' => null, 'domain' => null));
        $request = new Request();
        $response = new Response();
        $token = $this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\Token\TokenInterface')->getMock();

        $tokenProvider = $this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\RememberMe\TokenProviderInterface')->getMock();
        $tokenProvider
            ->expects($this->never())
            ->method('deleteTokenBySeries')
        ;
        $service->setTokenProvider($tokenProvider);

        $service->logout($request, $response, $token);

        $cookie = $request->attributes->get(RememberMeServicesInterface::COOKIE_ATTR_NAME);
        $this->assertTrue($cookie->isCleared());
        $this->assertEquals('/', $cookie->getPath());
        $this->assertNull($cookie->getDomain());
    }

    public function testLogoutSimplyIgnoresInvalidCookie()
    {
        $service = $this->getService(null, array('name' => 'foo', 'path' => null, 'domain' => null));
        $request = new Request();
        $request->cookies->set('foo', 'somefoovalue');
        $response = new Response();
        $token = $this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\Token\TokenInterface')->getMock();

        $tokenProvider = $this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\RememberMe\TokenProviderInterface')->getMock();
        $tokenProvider
            ->expects($this->never())
            ->method('deleteTokenBySeries')
        ;
        $service->setTokenProvider($tokenProvider);

        $service->logout($request, $response, $token);

        $this->assertTrue($request->attributes->get(RememberMeServicesInterface::COOKIE_ATTR_NAME)->isCleared());
    }

    public function testLoginFail()
    {
        $service = $this->getService(null, array('name' => 'foo', 'path' => null, 'domain' => null));
        $request = new Request();

        $this->assertFalse($request->attributes->has(RememberMeServicesInterface::COOKIE_ATTR_NAME));
        $service->loginFail($request);
        $this->assertTrue($request->attributes->get(RememberMeServicesInterface::COOKIE_ATTR_NAME)->isCleared());
    }

    public function testLoginSuccessSetsCookieWhenLoggedInWithNonRememberMeTokenInterfaceImplementation()
    {
        $service = $this->getService(null, array('name' => 'foo', 'domain' => 'myfoodomain.foo', 'path' => '/foo/path', 'secure' => true, 'httponly' => true, 'lifetime' => 3600, 'always_remember_me' => true));
        $request = new Request();
        $response = new Response();

        $account = $this->getMockBuilder('Symfony2\Component\Security\Core\User\UserInterface')->getMock();
        $account
            ->expects($this->once())
            ->method('getUsername')
            ->will($this->returnValue('foo'))
        ;
        $token = $this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\Token\TokenInterface')->getMock();
        $token
            ->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue($account))
        ;

        $tokenProvider = $this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\RememberMe\TokenProviderInterface')->getMock();
        $tokenProvider
            ->expects($this->once())
            ->method('createNewToken')
        ;
        $service->setTokenProvider($tokenProvider);

        $cookies = $response->headers->getCookies();
        $this->assertCount(0, $cookies);

        $service->loginSuccess($request, $response, $token);

        $cookies = $response->headers->getCookies(ResponseHeaderBag::COOKIES_ARRAY);
        $cookie = $cookies['myfoodomain.foo']['/foo/path']['foo'];
        $this->assertFalse($cookie->isCleared());
        $this->assertTrue($cookie->isSecure());
        $this->assertTrue($cookie->isHttpOnly());
        $this->assertTrue($cookie->getExpiresTime() > time() + 3590 && $cookie->getExpiresTime() < time() + 3610);
        $this->assertEquals('myfoodomain.foo', $cookie->getDomain());
        $this->assertEquals('/foo/path', $cookie->getPath());
    }

    protected function encodeCookie(array $parts)
    {
        $service = $this->getService();
        $r = new \ReflectionMethod($service, 'encodeCookie');
        $r->setAccessible(true);

        return $r->invoke($service, $parts);
    }

    protected function getService($userProvider = null, $options = array(), $logger = null)
    {
        if (null === $userProvider) {
            $userProvider = $this->getProvider();
        }

        return new PersistentTokenBasedRememberMeServices(array($userProvider), 'foosecret', 'fookey', $options, $logger);
    }

    protected function getProvider()
    {
        $provider = $this->getMockBuilder('Symfony2\Component\Security\Core\User\UserProviderInterface')->getMock();
        $provider
            ->expects($this->any())
            ->method('supportsClass')
            ->will($this->returnValue(true))
        ;

        return $provider;
    }
}
