<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\SecurityBundle\Tests\Functional\Bundle\FormLoginBundle\Security;

use Symfony2\Component\HttpFoundation\RedirectResponse;
use Symfony2\Component\HttpFoundation\Request;
use Symfony2\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony2\Component\Routing\RouterInterface;
use Symfony2\Component\Security\Core\Exception\AuthenticationException;
use Symfony2\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;

class LocalizedFormFailureHandler implements AuthenticationFailureHandlerInterface
{
    private $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return new RedirectResponse($this->router->generate('localized_login_path', array(), UrlGeneratorInterface::ABSOLUTE_URL));
    }
}
