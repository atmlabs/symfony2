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
use Symfony2\Component\Security\Core\Exception\AuthenticationException;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 */
class CustomAuthenticationFailureHandler implements AuthenticationFailureHandlerInterface
{
    private $handler;

    /**
     * @param AuthenticationFailureHandlerInterface $handler An AuthenticationFailureHandlerInterface instance
     * @param array                                 $options Options for processing a successful authentication attempt
     */
    public function __construct(AuthenticationFailureHandlerInterface $handler, array $options)
    {
        $this->handler = $handler;
        if (method_exists($handler, 'setOptions')) {
            $this->handler->setOptions($options);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return $this->handler->onAuthenticationFailure($request, $exception);
    }
}
