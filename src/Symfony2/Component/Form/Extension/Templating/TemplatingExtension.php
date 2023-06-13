<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Form\Extension\Templating;

use Symfony2\Bundle\FrameworkBundle\Templating\Helper\FormHelper;
use Symfony2\Component\Form\AbstractExtension;
use Symfony2\Component\Form\Exception\UnexpectedTypeException;
use Symfony2\Component\Form\Extension\Csrf\CsrfProvider\CsrfProviderAdapter;
use Symfony2\Component\Form\Extension\Csrf\CsrfProvider\CsrfProviderInterface;
use Symfony2\Component\Form\FormRenderer;
use Symfony2\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony2\Component\Templating\PhpEngine;

/**
 * Integrates the Templating component with the Form library.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class TemplatingExtension extends AbstractExtension
{
    public function __construct(PhpEngine $engine, $csrfTokenManager = null, array $defaultThemes = array())
    {
        if ($csrfTokenManager instanceof CsrfProviderInterface) {
            $csrfTokenManager = new CsrfProviderAdapter($csrfTokenManager);
        } elseif (null !== $csrfTokenManager && !$csrfTokenManager instanceof CsrfTokenManagerInterface) {
            throw new UnexpectedTypeException($csrfTokenManager, 'CsrfProviderInterface or CsrfTokenManagerInterface');
        }

        $engine->addHelpers(array(
            new FormHelper(new FormRenderer(new TemplatingRendererEngine($engine, $defaultThemes), $csrfTokenManager)),
        ));
    }
}
