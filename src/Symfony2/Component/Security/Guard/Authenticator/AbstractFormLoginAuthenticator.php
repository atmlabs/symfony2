<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Security\Guard\Authenticator;

use Symfony2\Component\HttpFoundation\RedirectResponse;
use Symfony2\Component\HttpFoundation\Request;
use Symfony2\Component\HttpFoundation\Session\SessionInterface;
use Symfony2\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony2\Component\Security\Core\Exception\AuthenticationException;
use Symfony2\Component\Security\Core\Security;
use Symfony2\Component\Security\Guard\AbstractGuardAuthenticator;

/**
 * A base class to make form login authentication easier!
 *
 * @author Ryan Weaver <ryan@knpuniversity.com>
 */
abstract class AbstractFormLoginAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * Return the URL to the login page.
     *
     * @return string
     */
    abstract protected function getLoginUrl();

    /**
     * The user will be redirected to the secure page they originally tried
     * to access. But if no such page exists (i.e. the user went to the
     * login page directly), this returns the URL the user should be redirected
     * to after logging in successfully (e.g. your homepage).
     *
     * @return string
     */
    abstract protected function getDefaultSuccessRedirectUrl();

    /**
     * Override to change what happens after a bad username/password is submitted.
     *
     * @param Request                 $request
     * @param AuthenticationException $exception
     *
     * @return RedirectResponse
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        if ($request->getSession() instanceof SessionInterface) {
            $request->getSession()->set(Security::AUTHENTICATION_ERROR, $exception);
        }

        $url = $this->getLoginUrl();

        return new RedirectResponse($url);
    }

    /**
     * Override to change what happens after successful authentication.
     *
     * @param Request        $request
     * @param TokenInterface $token
     * @param string         $providerKey
     *
     * @return RedirectResponse
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $targetPath = null;

        // if the user hit a secure page and start() was called, this was
        // the URL they were on, and probably where you want to redirect to
        if ($request->getSession() instanceof SessionInterface) {
            $targetPath = $request->getSession()->get('_security.'.$providerKey.'.target_path');
        }

        if (!$targetPath) {
            $targetPath = $this->getDefaultSuccessRedirectUrl();
        }

        return new RedirectResponse($targetPath);
    }

    public function supportsRememberMe()
    {
        return true;
    }

    /**
     * Override to control what happens when the user hits a secure page
     * but isn't logged in yet.
     *
     * @param Request                      $request
     * @param AuthenticationException|null $authException
     *
     * @return RedirectResponse
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $url = $this->getLoginUrl();

        return new RedirectResponse($url);
    }
}
