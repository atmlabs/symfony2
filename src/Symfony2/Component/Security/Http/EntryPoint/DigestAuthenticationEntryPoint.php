<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Security\Http\EntryPoint;

use Psr\Log\LoggerInterface;
use Symfony2\Component\HttpFoundation\Request;
use Symfony2\Component\HttpFoundation\Response;
use Symfony2\Component\Security\Core\Exception\AuthenticationException;
use Symfony2\Component\Security\Core\Exception\NonceExpiredException;

/**
 * DigestAuthenticationEntryPoint starts an HTTP Digest authentication.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class DigestAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    private $secret;
    private $realmName;
    private $nonceValiditySeconds;
    private $logger;

    public function __construct($realmName, $secret, $nonceValiditySeconds = 300, LoggerInterface $logger = null)
    {
        $this->realmName = $realmName;
        $this->secret = $secret;
        $this->nonceValiditySeconds = $nonceValiditySeconds;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $expiryTime = microtime(true) + $this->nonceValiditySeconds * 1000;
        $signatureValue = md5($expiryTime.':'.$this->secret);
        $nonceValue = $expiryTime.':'.$signatureValue;
        $nonceValueBase64 = base64_encode($nonceValue);

        $authenticateHeader = sprintf('Digest realm="%s", qop="auth", nonce="%s"', $this->realmName, $nonceValueBase64);

        if ($authException instanceof NonceExpiredException) {
            $authenticateHeader .= ', stale="true"';
        }

        if (null !== $this->logger) {
            $this->logger->debug('WWW-Authenticate header sent.', array('header' => $authenticateHeader));
        }

        $response = new Response();
        $response->headers->set('WWW-Authenticate', $authenticateHeader);
        $response->setStatusCode(401);

        return $response;
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
     * @return string
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * @return string
     */
    public function getRealmName()
    {
        return $this->realmName;
    }
}
