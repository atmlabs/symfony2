<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Security\Core\Tests\Authentication\Provider;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\Security\Core\Authentication\Provider\AnonymousAuthenticationProvider;

class AnonymousAuthenticationProviderTest extends TestCase
{
    public function testSupports()
    {
        $provider = $this->getProvider('foo');

        $this->assertTrue($provider->supports($this->getSupportedToken('foo')));
        $this->assertFalse($provider->supports($this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\Token\TokenInterface')->getMock()));
    }

    /**
     * @expectedException \Symfony2\Component\Security\Core\Exception\AuthenticationException
     * @expectedExceptionMessage The token is not supported by this authentication provider.
     */
    public function testAuthenticateWhenTokenIsNotSupported()
    {
        $provider = $this->getProvider('foo');

        $provider->authenticate($this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\Token\TokenInterface')->getMock());
    }

    /**
     * @expectedException \Symfony2\Component\Security\Core\Exception\BadCredentialsException
     */
    public function testAuthenticateWhenSecretIsNotValid()
    {
        $provider = $this->getProvider('foo');

        $provider->authenticate($this->getSupportedToken('bar'));
    }

    public function testAuthenticate()
    {
        $provider = $this->getProvider('foo');
        $token = $this->getSupportedToken('foo');

        $this->assertSame($token, $provider->authenticate($token));
    }

    protected function getSupportedToken($secret)
    {
        $token = $this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\Token\AnonymousToken')->setMethods(array('getSecret'))->disableOriginalConstructor()->getMock();
        $token->expects($this->any())
              ->method('getSecret')
              ->will($this->returnValue($secret))
        ;

        return $token;
    }

    protected function getProvider($secret)
    {
        return new AnonymousAuthenticationProvider($secret);
    }
}
