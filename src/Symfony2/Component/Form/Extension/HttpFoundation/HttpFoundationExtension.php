<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Form\Extension\HttpFoundation;

use Symfony2\Component\Form\AbstractExtension;

/**
 * Integrates the HttpFoundation component with the Form library.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class HttpFoundationExtension extends AbstractExtension
{
    protected function loadTypeExtensions()
    {
        return array(
            new Type\FormTypeHttpFoundationExtension(),
        );
    }
}
