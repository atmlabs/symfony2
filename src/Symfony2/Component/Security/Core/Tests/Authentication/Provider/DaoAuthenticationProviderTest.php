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
use Symfony2\Component\Security\Core\Authentication\Provider\DaoAuthenticationProvider;
use Symfony2\Component\Security\Core\Encoder\PlaintextPasswordEncoder;
use Symfony2\Component\Security\Core\Exception\UsernameNotFoundException;

class DaoAuthenticationProviderTest extends TestCase
{
    /**
     * @expectedException \Symfony2\Component\Security\Core\Exception\AuthenticationServiceException
     */
    public function testRetrieveUserWhenProviderDoesNotReturnAnUserInterface()
    {
        $provider = $this->getProvider('fabien');
        $method = new \ReflectionMethod($provider, 'retrieveUser');
        $method->setAccessible(true);

        $method->invoke($provider, 'fabien', $this->getSupportedToken());
    }

    /**
     * @expectedException \Symfony2\Component\Security\Core\Exception\UsernameNotFoundException
     */
    public function testRetrieveUserWhenUsernameIsNotFound()
    {
        $userProvider = $this->getMockBuilder('Symfony2\\Component\\Security\\Core\\User\\UserProviderInterface')->getMock();
        $userProvider->expects($this->once())
                     ->method('loadUserByUsername')
                     ->will($this->throwException(new UsernameNotFoundException()))
        ;

        $provider = new DaoAuthenticationProvider($userProvider, $this->getMockBuilder('Symfony2\\Component\\Security\\Core\\User\\UserCheckerInterface')->getMock(), 'key', $this->getMockBuilder('Symfony2\\Component\\Security\\Core\\Encoder\\EncoderFactoryInterface')->getMock());
        $method = new \ReflectionMethod($provider, 'retrieveUser');
        $method->setAccessible(true);

        $method->invoke($provider, 'fabien', $this->getSupportedToken());
    }

    /**
     * @expectedException \Symfony2\Component\Security\Core\Exception\AuthenticationServiceException
     */
    public function testRetrieveUserWhenAnExceptionOccurs()
    {
        $userProvider = $this->getMockBuilder('Symfony2\\Component\\Security\\Core\\User\\UserProviderInterface')->getMock();
        $userProvider->expects($this->once())
                     ->method('loadUserByUsername')
                     ->will($this->throwException(new \RuntimeException()))
        ;

        $provider = new DaoAuthenticationProvider($userProvider, $this->getMockBuilder('Symfony2\\Component\\Security\\Core\\User\\UserCheckerInterface')->getMock(), 'key', $this->getMockBuilder('Symfony2\\Component\\Security\\Core\\Encoder\\EncoderFactoryInterface')->getMock());
        $method = new \ReflectionMethod($provider, 'retrieveUser');
        $method->setAccessible(true);

        $method->invoke($provider, 'fabien', $this->getSupportedToken());
    }

    public function testRetrieveUserReturnsUserFromTokenOnReauthentication()
    {
        $userProvider = $this->getMockBuilder('Symfony2\\Component\\Security\\Core\\User\\UserProviderInterface')->getMock();
        $userProvider->expects($this->never())
                     ->method('loadUserByUsername')
        ;

        $user = $this->getMockBuilder('Symfony2\\Component\\Security\\Core\\User\\UserInterface')->getMock();
        $token = $this->getSupportedToken();
        $token->expects($this->once())
              ->method('getUser')
              ->will($this->returnValue($user))
        ;

        $provider = new DaoAuthenticationProvider($userProvider, $this->getMockBuilder('Symfony2\\Component\\Security\\Core\\User\\UserCheckerInterface')->getMock(), 'key', $this->getMockBuilder('Symfony2\\Component\\Security\\Core\\Encoder\\EncoderFactoryInterface')->getMock());
        $reflection = new \ReflectionMethod($provider, 'retrieveUser');
        $reflection->setAccessible(true);
        $result = $reflection->invoke($provider, null, $token);

        $this->assertSame($user, $result);
    }

    public function testRetrieveUser()
    {
        $user = $this->getMockBuilder('Symfony2\\Component\\Security\\Core\\User\\UserInterface')->getMock();

        $userProvider = $this->getMockBuilder('Symfony2\\Component\\Security\\Core\\User\\UserProviderInterface')->getMock();
        $userProvider->expects($this->once())
                     ->method('loadUserByUsername')
                     ->will($this->returnValue($user))
        ;

        $provider = new DaoAuthenticationProvider($userProvider, $this->getMockBuilder('Symfony2\\Component\\Security\\Core\\User\\UserCheckerInterface')->getMock(), 'key', $this->getMockBuilder('Symfony2\\Component\\Security\\Core\\Encoder\\EncoderFactoryInterface')->getMock());
        $method = new \ReflectionMethod($provider, 'retrieveUser');
        $method->setAccessible(true);

        $this->assertSame($user, $method->invoke($provider, 'fabien', $this->getSupportedToken()));
    }

    /**
     * @expectedException \Symfony2\Component\Security\Core\Exception\BadCredentialsException
     */
    public function testCheckAuthenticationWhenCredentialsAreEmpty()
    {
        $encoder = $this->getMockBuilder('Symfony2\\Component\\Security\\Core\\Encoder\\PasswordEncoderInterface')->getMock();
        $encoder
            ->expects($this->never())
            ->method('isPasswordValid')
        ;

        $provider = $this->getProvider(null, null, $encoder);
        $method = new \ReflectionMethod($provider, 'checkAuthentication');
        $method->setAccessible(true);

        $token = $this->getSupportedToken();
        $token
            ->expects($this->once())
            ->method('getCredentials')
            ->will($this->returnValue(''))
        ;

        $method->invoke(
            $provider,
            $this->getMockBuilder('Symfony2\\Component\\Security\\Core\\User\\UserInterface')->getMock(),
            $token
        );
    }

    public function testCheckAuthenticationWhenCredentialsAre0()
    {
        $encoder = $this->getMockBuilder('Symfony2\\Component\\Security\\Core\\Encoder\\PasswordEncoderInterface')->getMock();
        $encoder
            ->expects($this->once())
            ->method('isPasswordValid')
            ->will($this->returnValue(true))
        ;

        $provider = $this->getProvider(null, null, $encoder);
        $method = new \ReflectionMethod($provider, 'checkAuthentication');
        $method->setAccessible(true);

        $token = $this->getSupportedToken();
        $token
            ->expects($this->once())
            ->method('getCredentials')
            ->will($this->returnValue('0'))
        ;

        $method->invoke(
            $provider,
            $this->getMockBuilder('Symfony2\\Component\\Security\\Core\\User\\UserInterface')->getMock(),
            $token
        );
    }

    /**
     * @expectedException \Symfony2\Component\Security\Core\Exception\BadCredentialsException
     */
    public function testCheckAuthenticationWhenCredentialsAreNotValid()
    {
        $encoder = $this->getMockBuilder('Symfony2\\Component\\Security\\Core\\Encoder\\PasswordEncoderInterface')->getMock();
        $encoder->expects($this->once())
                ->method('isPasswordValid')
                ->will($this->returnValue(false))
        ;

        $provider = $this->getProvider(null, null, $encoder);
        $method = new \ReflectionMethod($provider, 'checkAuthentication');
        $method->setAccessible(true);

        $token = $this->getSupportedToken();
        $token->expects($this->once())
              ->method('getCredentials')
              ->will($this->returnValue('foo'))
        ;

        $method->invoke($provider, $this->getMockBuilder('Symfony2\\Component\\Security\\Core\\User\\UserInterface')->getMock(), $token);
    }

    /**
     * @expectedException \Symfony2\Component\Security\Core\Exception\BadCredentialsException
     */
    public function testCheckAuthenticationDoesNotReauthenticateWhenPasswordHasChanged()
    {
        $user = $this->getMockBuilder('Symfony2\\Component\\Security\\Core\\User\\UserInterface')->getMock();
        $user->expects($this->once())
             ->method('getPassword')
             ->will($this->returnValue('foo'))
        ;

        $token = $this->getSupportedToken();
        $token->expects($this->once())
              ->method('getUser')
              ->will($this->returnValue($user));

        $dbUser = $this->getMockBuilder('Symfony2\\Component\\Security\\Core\\User\\UserInterface')->getMock();
        $dbUser->expects($this->once())
               ->method('getPassword')
               ->will($this->returnValue('newFoo'))
        ;

        $provider = $this->getProvider();
        $reflection = new \ReflectionMethod($provider, 'checkAuthentication');
        $reflection->setAccessible(true);
        $reflection->invoke($provider, $dbUser, $token);
    }

    public function testCheckAuthenticationWhenTokenNeedsReauthenticationWorksWithoutOriginalCredentials()
    {
        $user = $this->getMockBuilder('Symfony2\\Component\\Security\\Core\\User\\UserInterface')->getMock();
        $user->expects($this->once())
             ->method('getPassword')
             ->will($this->returnValue('foo'))
        ;

        $token = $this->getSupportedToken();
        $token->expects($this->once())
              ->method('getUser')
              ->will($this->returnValue($user));

        $dbUser = $this->getMockBuilder('Symfony2\\Component\\Security\\Core\\User\\UserInterface')->getMock();
        $dbUser->expects($this->once())
               ->method('getPassword')
               ->will($this->returnValue('foo'))
        ;

        $provider = $this->getProvider();
        $reflection = new \ReflectionMethod($provider, 'checkAuthentication');
        $reflection->setAccessible(true);
        $reflection->invoke($provider, $dbUser, $token);
    }

    public function testCheckAuthentication()
    {
        $encoder = $this->getMockBuilder('Symfony2\\Component\\Security\\Core\\Encoder\\PasswordEncoderInterface')->getMock();
        $encoder->expects($this->once())
                ->method('isPasswordValid')
                ->will($this->returnValue(true))
        ;

        $provider = $this->getProvider(null, null, $encoder);
        $method = new \ReflectionMethod($provider, 'checkAuthentication');
        $method->setAccessible(true);

        $token = $this->getSupportedToken();
        $token->expects($this->once())
              ->method('getCredentials')
              ->will($this->returnValue('foo'))
        ;

        $method->invoke($provider, $this->getMockBuilder('Symfony2\\Component\\Security\\Core\\User\\UserInterface')->getMock(), $token);
    }

    protected function getSupportedToken()
    {
        $mock = $this->getMockBuilder('Symfony2\\Component\\Security\\Core\\Authentication\\Token\\UsernamePasswordToken')->setMethods(array('getCredentials', 'getUser', 'getProviderKey'))->disableOriginalConstructor()->getMock();
        $mock
            ->expects($this->any())
            ->method('getProviderKey')
            ->will($this->returnValue('key'))
        ;

        return $mock;
    }

    protected function getProvider($user = null, $userChecker = null, $passwordEncoder = null)
    {
        $userProvider = $this->getMockBuilder('Symfony2\\Component\\Security\\Core\\User\\UserProviderInterface')->getMock();
        if (null !== $user) {
            $userProvider->expects($this->once())
                         ->method('loadUserByUsername')
                         ->will($this->returnValue($user))
            ;
        }

        if (null === $userChecker) {
            $userChecker = $this->getMockBuilder('Symfony2\\Component\\Security\\Core\\User\\UserCheckerInterface')->getMock();
        }

        if (null === $passwordEncoder) {
            $passwordEncoder = new PlaintextPasswordEncoder();
        }

        $encoderFactory = $this->getMockBuilder('Symfony2\\Component\\Security\\Core\\Encoder\\EncoderFactoryInterface')->getMock();
        $encoderFactory
            ->expects($this->any())
            ->method('getEncoder')
            ->will($this->returnValue($passwordEncoder))
        ;

        return new DaoAuthenticationProvider($userProvider, $userChecker, 'key', $encoderFactory);
    }
}
