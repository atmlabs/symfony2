<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Form\Extension\Csrf;

use Symfony2\Component\Form\AbstractExtension;
use Symfony2\Component\Form\Exception\UnexpectedTypeException;
use Symfony2\Component\Form\Extension\Csrf\CsrfProvider\CsrfProviderAdapter;
use Symfony2\Component\Form\Extension\Csrf\CsrfProvider\CsrfProviderInterface;
use Symfony2\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony2\Component\Translation\TranslatorInterface;

/**
 * This extension protects forms by using a CSRF token.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class CsrfExtension extends AbstractExtension
{
    private $tokenManager;
    private $translator;
    private $translationDomain;

    /**
     * @param CsrfTokenManagerInterface $tokenManager      The CSRF token manager
     * @param TranslatorInterface       $translator        The translator for translating error messages
     * @param string|null               $translationDomain The translation domain for translating
     */
    public function __construct($tokenManager, TranslatorInterface $translator = null, $translationDomain = null)
    {
        if ($tokenManager instanceof CsrfProviderInterface) {
            $tokenManager = new CsrfProviderAdapter($tokenManager);
        } elseif (!$tokenManager instanceof CsrfTokenManagerInterface) {
            throw new UnexpectedTypeException($tokenManager, 'CsrfProviderInterface or CsrfTokenManagerInterface');
        }

        $this->tokenManager = $tokenManager;
        $this->translator = $translator;
        $this->translationDomain = $translationDomain;
    }

    /**
     * {@inheritdoc}
     */
    protected function loadTypeExtensions()
    {
        return array(
            new Type\FormTypeCsrfExtension($this->tokenManager, true, '_token', $this->translator, $this->translationDomain),
        );
    }
}
