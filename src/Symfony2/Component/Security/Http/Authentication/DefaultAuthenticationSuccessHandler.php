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
use Symfony2\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony2\Component\Security\Http\HttpUtils;
use Symfony2\Component\Security\Http\ParameterBagUtils;

/**
 * Class with the default authentication success handling logic.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 * @author Alexander <iam.asm89@gmail.com>
 */
class DefaultAuthenticationSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    protected $httpUtils;
    protected $options;
    protected $providerKey;
    protected $defaultOptions = array(
        'always_use_default_target_path' => false,
        'default_target_path' => '/',
        'login_path' => '/login',
        'target_path_parameter' => '_target_path',
        'use_referer' => false,
    );

    /**
     * @param HttpUtils $httpUtils
     * @param array     $options   Options for processing a successful authentication attempt
     */
    public function __construct(HttpUtils $httpUtils, array $options = array())
    {
        $this->httpUtils = $httpUtils;
        $this->setOptions($options);
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token)
    {
        return $this->httpUtils->createRedirectResponse($request, $this->determineTargetUrl($request));
    }

    /**
     * Gets the options.
     *
     * @return array An array of options
     */
    public function getOptions()
    {
        return $this->options;
    }

    public function setOptions(array $options)
    {
        $this->options = array_merge($this->defaultOptions, $options);
    }

    /**
     * Get the provider key.
     *
     * @return string
     */
    public function getProviderKey()
    {
        return $this->providerKey;
    }

    /**
     * Set the provider key.
     *
     * @param string $providerKey
     */
    public function setProviderKey($providerKey)
    {
        $this->providerKey = $providerKey;
    }

    /**
     * Builds the target URL according to the defined options.
     *
     * @return string
     */
    protected function determineTargetUrl(Request $request)
    {
        if ($this->options['always_use_default_target_path']) {
            return $this->options['default_target_path'];
        }

        if ($targetUrl = ParameterBagUtils::getRequestParameterValue($request, $this->options['target_path_parameter'])) {
            return $targetUrl;
        }

        if (null !== $this->providerKey && $targetUrl = $request->getSession()->get('_security.'.$this->providerKey.'.target_path')) {
            $request->getSession()->remove('_security.'.$this->providerKey.'.target_path');

            return $targetUrl;
        }

        if ($this->options['use_referer'] && $targetUrl = $request->headers->get('Referer')) {
            if (false !== $pos = strpos($targetUrl, '?')) {
                $targetUrl = substr($targetUrl, 0, $pos);
            }
            if ($targetUrl && $targetUrl !== $this->httpUtils->generateUri($request, $this->options['login_path'])) {
                return $targetUrl;
            }
        }

        return $this->options['default_target_path'];
    }
}
