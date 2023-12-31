<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Security\Core\Tests\Validator\Constraints;

use Symfony2\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony2\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony2\Component\Security\Core\Encoder\PasswordEncoderInterface;
use Symfony2\Component\Security\Core\Validator\Constraints\UserPassword;
use Symfony2\Component\Security\Core\Validator\Constraints\UserPasswordValidator;
use Symfony2\Component\Validator\Tests\Constraints\AbstractConstraintValidatorTest;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class UserPasswordValidatorTest extends AbstractConstraintValidatorTest
{
    const PASSWORD = 's3Cr3t';
    const SALT = '^S4lt$';

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var PasswordEncoderInterface
     */
    protected $encoder;

    /**
     * @var EncoderFactoryInterface
     */
    protected $encoderFactory;

    protected function createValidator()
    {
        return new UserPasswordValidator($this->tokenStorage, $this->encoderFactory);
    }

    protected function setUp()
    {
        $user = $this->createUser();
        $this->tokenStorage = $this->createTokenStorage($user);
        $this->encoder = $this->createPasswordEncoder();
        $this->encoderFactory = $this->createEncoderFactory($this->encoder);

        parent::setUp();
    }

    public function testPasswordIsValid()
    {
        $constraint = new UserPassword(array(
            'message' => 'myMessage',
        ));

        $this->encoder->expects($this->once())
            ->method('isPasswordValid')
            ->with(static::PASSWORD, 'secret', static::SALT)
            ->will($this->returnValue(true));

        $this->validator->validate('secret', $constraint);

        $this->assertNoViolation();
    }

    public function testPasswordIsNotValid()
    {
        $constraint = new UserPassword(array(
            'message' => 'myMessage',
        ));

        $this->encoder->expects($this->once())
            ->method('isPasswordValid')
            ->with(static::PASSWORD, 'secret', static::SALT)
            ->will($this->returnValue(false));

        $this->validator->validate('secret', $constraint);

        $this->buildViolation('myMessage')
            ->assertRaised();
    }

    /**
     * @dataProvider emptyPasswordData
     */
    public function testEmptyPasswordsAreNotValid($password)
    {
        $constraint = new UserPassword(array(
            'message' => 'myMessage',
        ));

        $this->validator->validate($password, $constraint);

        $this->buildViolation('myMessage')
            ->assertRaised();
    }

    public function emptyPasswordData()
    {
        return array(
            array(null),
            array(''),
        );
    }

    /**
     * @expectedException \Symfony2\Component\Validator\Exception\ConstraintDefinitionException
     */
    public function testUserIsNotValid()
    {
        $user = $this->getMockBuilder('Foo\Bar\User')->getMock();

        $this->tokenStorage = $this->createTokenStorage($user);
        $this->validator = $this->createValidator();
        $this->validator->initialize($this->context);

        $this->validator->validate('secret', new UserPassword());
    }

    protected function createUser()
    {
        $mock = $this->getMockBuilder('Symfony2\Component\Security\Core\User\UserInterface')->getMock();

        $mock
            ->expects($this->any())
            ->method('getPassword')
            ->will($this->returnValue(static::PASSWORD))
        ;

        $mock
            ->expects($this->any())
            ->method('getSalt')
            ->will($this->returnValue(static::SALT))
        ;

        return $mock;
    }

    protected function createPasswordEncoder($isPasswordValid = true)
    {
        return $this->getMockBuilder('Symfony2\Component\Security\Core\Encoder\PasswordEncoderInterface')->getMock();
    }

    protected function createEncoderFactory($encoder = null)
    {
        $mock = $this->getMockBuilder('Symfony2\Component\Security\Core\Encoder\EncoderFactoryInterface')->getMock();

        $mock
            ->expects($this->any())
            ->method('getEncoder')
            ->will($this->returnValue($encoder))
        ;

        return $mock;
    }

    protected function createTokenStorage($user = null)
    {
        $token = $this->createAuthenticationToken($user);

        $mock = $this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface')->getMock();
        $mock
            ->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue($token))
        ;

        return $mock;
    }

    protected function createAuthenticationToken($user = null)
    {
        $mock = $this->getMockBuilder('Symfony2\Component\Security\Core\Authentication\Token\TokenInterface')->getMock();
        $mock
            ->expects($this->any())
            ->method('getUser')
            ->will($this->returnValue($user))
        ;

        return $mock;
    }
}
