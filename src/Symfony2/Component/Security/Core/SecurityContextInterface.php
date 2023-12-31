<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Security\Core;

use Symfony2\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony2\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

/**
 * The SecurityContextInterface.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * @deprecated since version 2.6, to be removed in 3.0.
 */
interface SecurityContextInterface extends TokenStorageInterface, AuthorizationCheckerInterface
{
    const ACCESS_DENIED_ERROR = Security::ACCESS_DENIED_ERROR;
    const AUTHENTICATION_ERROR = Security::AUTHENTICATION_ERROR;
    const LAST_USERNAME = Security::LAST_USERNAME;
    const MAX_USERNAME_LENGTH = Security::MAX_USERNAME_LENGTH;
}
