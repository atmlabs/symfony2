<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Security\Http\Authentication;

use Symfony2\Component\HttpFoundation\Request;
use Symfony2\Component\HttpFoundation\Response;
use Symfony2\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * Interface for a custom authentication success handler.
 *
 * If you want to customize the success handling process, instead of
 * overwriting the respective listener globally, you can set a custom success
 * handler which implements this interface.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface AuthenticationSuccessHandlerInterface
{
    /**
     * This is called when an interactive authentication attempt succeeds. This
     * is called by authentication listeners inheriting from
     * AbstractAuthenticationListener.
     *
     * @return Response never null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token);
}
