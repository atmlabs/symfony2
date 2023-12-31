<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Form\Extension\Csrf\CsrfProvider;

@trigger_error('The '.__NAMESPACE__.'\SessionCsrfProvider is deprecated since Symfony 2.4 and will be removed in version 3.0. Use the Symfony2\Component\Security\Csrf\TokenStorage\SessionTokenStorage class instead.', E_USER_DEPRECATED);

use Symfony2\Component\HttpFoundation\Session\Session;

/**
 * This provider uses a Symfony Session object to retrieve the user's
 * session ID.
 *
 * @see DefaultCsrfProvider
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @deprecated since version 2.4, to be removed in 3.0.
 *             Use {@link \Symfony2\Component\Security\Csrf\CsrfTokenManager} in
 *             combination with {@link \Symfony2\Component\Security\Csrf\TokenStorage\SessionTokenStorage}
 *             instead.
 */
class SessionCsrfProvider extends DefaultCsrfProvider
{
    protected $session;

    /**
     * Initializes the provider with a Session object and a secret value.
     *
     * A recommended value for the secret is a generated value with at least
     * 32 characters and mixed letters, digits and special characters.
     *
     * @param Session $session The user session from which the session ID is returned
     * @param string  $secret  A secret value included in the CSRF token
     */
    public function __construct(Session $session, $secret)
    {
        parent::__construct($secret);

        $this->session = $session;
    }

    /**
     * {@inheritdoc}
     */
    protected function getSessionId()
    {
        $this->session->start();

        return $this->session->getId();
    }
}
