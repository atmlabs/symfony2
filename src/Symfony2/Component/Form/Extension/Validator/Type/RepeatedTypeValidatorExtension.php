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

use Symfony2\Component\Form\AbstractTypeExtension;
use Symfony2\Component\OptionsResolver\Options;
use Symfony2\Component\OptionsResolver\OptionsResolver;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class RepeatedTypeValidatorExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        // Map errors to the first field
        $errorMapping = function (Options $options) {
            return array('.' => $options['first_name']);
        };

        $resolver->setDefaults(array(
            'error_mapping' => $errorMapping,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'Symfony\Component\Form\Extension\Core\Type\RepeatedType';
    }
}
