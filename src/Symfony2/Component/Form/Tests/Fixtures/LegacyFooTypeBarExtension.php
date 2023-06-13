<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Form\Tests\Fixtures;

use Symfony2\Component\Form\AbstractTypeExtension;
use Symfony2\Component\Form\FormBuilderInterface;

class LegacyFooTypeBarExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->setAttribute('bar', 'x');
    }

    public function getAllowedOptionValues()
    {
        return array(
            'a_or_b' => array('c'),
        );
    }

    public function getExtendedType()
    {
        return 'foo';
    }
}
