<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Security\Core\Authentication\Token;

use Symfony2\Component\Security\Core\Role\RoleInterface;

/**
 * AnonymousToken represents an anonymous token.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class AnonymousToken extends AbstractToken
{
    private $secret;

    /**
     * @param string          $secret A secret used to make sure the token is created by the app and not by a malicious client
     * @param string|object   $user   The user can be a UserInterface instance, or an object implementing a __toString method or the username as a regular string
     * @param RoleInterface[] $roles  An array of roles
     */
    public function __construct($secret, $user, array $roles = array())
    {
        parent::__construct($roles);

        $this->secret = $secret;
        $this->setUser($user);
        $this->setAuthenticated(true);
    }

    /**
     * {@inheritdoc}
     */
    public function getCredentials()
    {
        return '';
    }

    /**
     * @deprecated Since version 2.8, to be removed in 3.0. Use getSecret() instead.
     */
    public function getKey()
    {
        @trigger_error(__METHOD__.'() is deprecated since Symfony 2.8 and will be removed in 3.0. Use getSecret() instead.', E_USER_DEPRECATED);

        return $this->getSecret();
    }

    /**
     * Returns the secret.
     *
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize()
    {
        return serialize(array($this->secret, parent::serialize()));
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized)
    {
        list($this->secret, $parentStr) = unserialize($serialized);
        parent::unserialize($parentStr);
    }
}
