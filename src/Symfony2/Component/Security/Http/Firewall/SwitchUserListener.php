<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Security\Http\Firewall;

use Psr\Log\LoggerInterface;
use Symfony2\Component\EventDispatcher\EventDispatcherInterface;
use Symfony2\Component\HttpFoundation\RedirectResponse;
use Symfony2\Component\HttpFoundation\Request;
use Symfony2\Component\HttpKernel\Event\GetResponseEvent;
use Symfony2\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony2\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony2\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony2\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony2\Component\Security\Core\Exception\AccessDeniedException;
use Symfony2\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony2\Component\Security\Core\Exception\AuthenticationException;
use Symfony2\Component\Security\Core\Role\SwitchUserRole;
use Symfony2\Component\Security\Core\User\UserCheckerInterface;
use Symfony2\Component\Security\Core\User\UserInterface;
use Symfony2\Component\Security\Core\User\UserProviderInterface;
use Symfony2\Component\Security\Http\Event\SwitchUserEvent;
use Symfony2\Component\Security\Http\SecurityEvents;

/**
 * SwitchUserListener allows a user to impersonate another one temporarily
 * (like the Unix su command).
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SwitchUserListener implements ListenerInterface
{
    private $tokenStorage;
    private $provider;
    private $userChecker;
    private $providerKey;
    private $accessDecisionManager;
    private $usernameParameter;
    private $role;
    private $logger;
    private $dispatcher;

    public function __construct(TokenStorageInterface $tokenStorage, UserProviderInterface $provider, UserCheckerInterface $userChecker, $providerKey, AccessDecisionManagerInterface $accessDecisionManager, LoggerInterface $logger = null, $usernameParameter = '_switch_user', $role = 'ROLE_ALLOWED_TO_SWITCH', EventDispatcherInterface $dispatcher = null)
    {
        if (empty($providerKey)) {
            throw new \InvalidArgumentException('$providerKey must not be empty.');
        }

        $this->tokenStorage = $tokenStorage;
        $this->provider = $provider;
        $this->userChecker = $userChecker;
        $this->providerKey = $providerKey;
        $this->accessDecisionManager = $accessDecisionManager;
        $this->usernameParameter = $usernameParameter;
        $this->role = $role;
        $this->logger = $logger;
        $this->dispatcher = $dispatcher;
    }

    /**
     * Handles the switch to another user.
     *
     * @throws \LogicException if switching to a user failed
     */
    public function handle(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if (!$request->get($this->usernameParameter)) {
            return;
        }

        if ('_exit' === $request->get($this->usernameParameter)) {
            $this->tokenStorage->setToken($this->attemptExitUser($request));
        } else {
            try {
                $this->tokenStorage->setToken($this->attemptSwitchUser($request));
            } catch (AuthenticationException $e) {
                throw new \LogicException(sprintf('Switch User failed: "%s"', $e->getMessage()));
            }
        }

        $request->query->remove($this->usernameParameter);
        $request->server->set('QUERY_STRING', http_build_query($request->query->all(), '', '&'));

        $response = new RedirectResponse($request->getUri(), 302);

        $event->setResponse($response);
    }

    /**
     * Attempts to switch to another user.
     *
     * @return TokenInterface|null The new TokenInterface if successfully switched, null otherwise
     *
     * @throws \LogicException
     * @throws AccessDeniedException
     */
    private function attemptSwitchUser(Request $request)
    {
        $token = $this->tokenStorage->getToken();
        $originalToken = $this->getOriginalToken($token);

        if (false !== $originalToken) {
            if ($token->getUsername() === $request->get($this->usernameParameter)) {
                return $token;
            }

            throw new \LogicException(sprintf('You are already switched to "%s" user.', $token->getUsername()));
        }

        if (false === $this->accessDecisionManager->decide($token, array($this->role))) {
            throw new AccessDeniedException();
        }

        $username = $request->get($this->usernameParameter);

        if (null !== $this->logger) {
            $this->logger->info('Attempting to switch to user.', array('username' => $username));
        }

        $user = $this->provider->loadUserByUsername($username);
        $this->userChecker->checkPostAuth($user);

        $roles = $user->getRoles();
        $roles[] = new SwitchUserRole('ROLE_PREVIOUS_ADMIN', $this->tokenStorage->getToken());

        $token = new UsernamePasswordToken($user, $user->getPassword(), $this->providerKey, $roles);

        if (null !== $this->dispatcher) {
            $switchEvent = new SwitchUserEvent($request, $token->getUser());
            $this->dispatcher->dispatch(SecurityEvents::SWITCH_USER, $switchEvent);
        }

        return $token;
    }

    /**
     * Attempts to exit from an already switched user.
     *
     * @return TokenInterface The original TokenInterface instance
     *
     * @throws AuthenticationCredentialsNotFoundException
     */
    private function attemptExitUser(Request $request)
    {
        if (null === ($currentToken = $this->tokenStorage->getToken()) || false === $original = $this->getOriginalToken($currentToken)) {
            throw new AuthenticationCredentialsNotFoundException('Could not find original Token object.');
        }

        if (null !== $this->dispatcher && $original->getUser() instanceof UserInterface) {
            $user = $this->provider->refreshUser($original->getUser());
            $switchEvent = new SwitchUserEvent($request, $user);
            $this->dispatcher->dispatch(SecurityEvents::SWITCH_USER, $switchEvent);
        }

        return $original;
    }

    /**
     * Gets the original Token from a switched one.
     *
     * @return TokenInterface|false The original TokenInterface instance, false if the current TokenInterface is not switched
     */
    private function getOriginalToken(TokenInterface $token)
    {
        foreach ($token->getRoles() as $role) {
            if ($role instanceof SwitchUserRole) {
                return $role->getSource();
            }
        }

        return false;
    }
}
