<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Security\Core\Authentication\Provider;

use Symfony2\Component\Ldap\Exception\ConnectionException;
use Symfony2\Component\Ldap\LdapClientInterface;
use Symfony2\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony2\Component\Security\Core\Exception\BadCredentialsException;
use Symfony2\Component\Security\Core\Exception\UsernameNotFoundException;
use Symfony2\Component\Security\Core\User\UserCheckerInterface;
use Symfony2\Component\Security\Core\User\UserInterface;
use Symfony2\Component\Security\Core\User\UserProviderInterface;

/**
 * LdapBindAuthenticationProvider authenticates a user against an LDAP server.
 *
 * The only way to check user credentials is to try to connect the user with its
 * credentials to the ldap.
 *
 * @author Charles Sarrazin <charles@sarraz.in>
 */
class LdapBindAuthenticationProvider extends UserAuthenticationProvider
{
    private $userProvider;
    private $ldap;
    private $dnString;

    /**
     * @param UserProviderInterface $userProvider               A UserProvider
     * @param UserCheckerInterface  $userChecker                A UserChecker
     * @param string                $providerKey                The provider key
     * @param LdapClientInterface   $ldap                       An Ldap client
     * @param string                $dnString                   A string used to create the bind DN
     * @param bool                  $hideUserNotFoundExceptions Whether to hide user not found exception or not
     */
    public function __construct(UserProviderInterface $userProvider, UserCheckerInterface $userChecker, $providerKey, LdapClientInterface $ldap, $dnString = '{username}', $hideUserNotFoundExceptions = true)
    {
        parent::__construct($userChecker, $providerKey, $hideUserNotFoundExceptions);

        $this->userProvider = $userProvider;
        $this->ldap = $ldap;
        $this->dnString = $dnString;
    }

    /**
     * {@inheritdoc}
     */
    protected function retrieveUser($username, UsernamePasswordToken $token)
    {
        if ('NONE_PROVIDED' === $username) {
            throw new UsernameNotFoundException('Username can not be null');
        }

        return $this->userProvider->loadUserByUsername($username);
    }

    /**
     * {@inheritdoc}
     */
    protected function checkAuthentication(UserInterface $user, UsernamePasswordToken $token)
    {
        $username = $token->getUsername();
        $password = $token->getCredentials();

        if ('' === (string) $password) {
            throw new BadCredentialsException('The presented password must not be empty.');
        }

        try {
            $username = $this->ldap->escape($username, '', LDAP_ESCAPE_DN);
            $dn = str_replace('{username}', $username, $this->dnString);

            $this->ldap->bind($dn, $password);
        } catch (ConnectionException $e) {
            throw new BadCredentialsException('The presented password is invalid.');
        }
    }
}
