<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Security\Core\Authorization;

use Symfony2\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * AccessDecisionManagerInterface makes authorization decisions.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
interface AccessDecisionManagerInterface
{
    /**
     * Decides whether the access is possible or not.
     *
     * @param TokenInterface $token      A TokenInterface instance
     * @param array          $attributes An array of attributes associated with the method being invoked
     * @param object         $object     The object to secure
     *
     * @return bool true if the access is granted, false otherwise
     */
    public function decide(TokenInterface $token, array $attributes, $object = null);

    /**
     * Checks if the access decision manager supports the given attribute.
     *
     * @param string $attribute An attribute
     *
     * @return bool true if this decision manager supports the attribute, false otherwise
     *
     * @deprecated since version 2.8, to be removed in 3.0.
     */
    public function supportsAttribute($attribute);

    /**
     * Checks if the access decision manager supports the given class.
     *
     * @param string $class A class name
     *
     * @return true if this decision manager can process the class
     *
     * @deprecated since version 2.8, to be removed in 3.0.
     */
    public function supportsClass($class);
}
