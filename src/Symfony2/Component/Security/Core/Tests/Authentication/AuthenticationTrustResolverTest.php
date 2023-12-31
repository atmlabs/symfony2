<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Security\Core\Tests\Authentication;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\Security\Core\Authentication\AuthenticationTrustResolver;

class AuthenticationTrustResolverTest extends TestCase
{
    public function testIsAnonymous()
    {
        $resolver = $this->getResolver();

        $this->assertFalse($resolver->isAnonymous(null));
        $this->assertFalse($resolver->isAnonymous($this->getToken()));
        $this->assertFalse($resolver->isAnonymous($this->getRememberMeToken()));
        $this->assertTrue($resolver->isAnonymous($this->getAnonymousToken()));
    }

    public function testIsRememberMe()
    {
        $resolver = $this->getResolver();

        $this->assertFalse($resolver->isRememberMe(null));
        $this->assertFalse($resolver->isRememberMe($this->getToken()));
        $this->assertFalse($resolver->isRememberMe($this->getAnonymousToken()));
        $this->assertTrue($resolver->isRememberMe($this->getRememberMeToken()));
    }

    public function testisFullFledged()
    {
        $resolver = $this->getResolver();

        $this->assertFalse($resolver->isFullFledged(null));
        $this->assertFalse($resolver->isFullFledged($this->getAnonymousToken()));
        $this->assertFalse($resolver->isFullFledged($this->getRememberMeToken()));
        $this->assertTrue($resolver->isFullFledged($this->getToken()));
    }

    protected function getToken()
    {
        return $this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\Token\TokenInterface')->getMock();
    }

    protected function getAnonymousToken()
    {
        return $this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\Token\AnonymousToken')->setConstructorArgs(array('', ''))->getMock();
    }

    protected function getRememberMeToken()
    {
        return $this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\Token\RememberMeToken')->setMethods(array('setPersistent'))->disableOriginalConstructor()->getMock();
    }

    protected function getResolver()
    {
        return new AuthenticationTrustResolver(
            'Symfony2\\Component\\Security\\Core\\Authentication\\Token\\AnonymousToken',
            'Symfony2\\Component\\Security\\Core\\Authentication\\Token\\RememberMeToken'
        );
    }
}
