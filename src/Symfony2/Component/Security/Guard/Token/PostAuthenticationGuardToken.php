<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Security\Guard\Token;

use Symfony2\Component\Security\Core\Authentication\Token\AbstractToken;
use Symfony2\Component\Security\Core\Role\RoleInterface;
use Symfony2\Component\Security\Core\User\UserInterface;

/**
 * Used as an "authenticated" token, though it could be set to not-authenticated later.
 *
 * If you're using Guard authentication, you *must* use a class that implements
 * GuardTokenInterface as your authenticated token (like this class).
 *
 * @author Ryan Weaver <ryan@knpuniversity.com>
 */
class PostAuthenticationGuardToken extends AbstractToken implements GuardTokenInterface
{
    private $providerKey;

    /**
     * @param UserInterface            $user        The user!
     * @param string                   $providerKey The provider (firewall) key
     * @param RoleInterface[]|string[] $roles       An array of roles
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(UserInterface $user, $providerKey, array $roles)
    {
        parent::__construct($roles);

        if (empty($providerKey)) {
            throw new \InvalidArgumentException('$providerKey (i.e. firewall key) must not be empty.');
        }

        $this->setUser($user);
        $this->providerKey = $providerKey;

        // this token is meant to be used after authentication success, so it is always authenticated
        // you could set it as non authenticated later if you need to
        parent::setAuthenticated(true);
    }

    /**
     * This is meant to be only an authenticated token, where credentials
     * have already been used and are thus cleared.
     *
     * {@inheritdoc}
     */
    public function getCredentials()
    {
        return array();
    }

    /**
     * Returns the provider (firewall) key.
     *
     * @return string
     */
    public function getProviderKey()
    {
        return $this->providerKey;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(array($this->providerKey, parent::serialize()));
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list($this->providerKey, $parentStr) = unserialize($serialized);
        parent::unserialize($parentStr);
    }
}
