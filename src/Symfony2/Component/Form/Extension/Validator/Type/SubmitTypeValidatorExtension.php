<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Form\Extension\Validator\Type;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class SubmitTypeValidatorExtension extends BaseValidatorExtension
{
    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'Symfony2\Component\Form\Extension\Core\Type\SubmitType';
    }
}
