<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Security\Http\Logout;

use Symfony2\Component\HttpFoundation\Request;
use Symfony2\Component\HttpFoundation\Response;
use Symfony2\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Interface that needs to be implemented by LogoutHandlers.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface LogoutHandlerInterface
{
    /**
     * This method is called by the LogoutListener when a user has requested
     * to be logged out. Usually, you would unset session variables, or remove
     * cookies, etc.
     */
    public function logout(Request $request, Response $response, TokenInterface $token);
}
