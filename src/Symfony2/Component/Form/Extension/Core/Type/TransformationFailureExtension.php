<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Form\Extension\Core\Type;

use Symfony2\Component\Form\AbstractTypeExtension;
use Symfony2\Component\Form\Extension\Core\EventListener\TransformationFailureListener;
use Symfony2\Component\Form\FormBuilderInterface;
use Symfony2\Component\Translation\TranslatorInterface;

/**
 * @author Christian Flothmann <christian.flothmann@sensiolabs.de>
 */
class TransformationFailureExtension extends AbstractTypeExtension
{
    private $translator;

    public function __construct(TranslatorInterface $translator = null)
    {
        $this->translator = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!isset($options['invalid_message']) && !isset($options['invalid_message_parameters'])) {
            $builder->addEventSubscriber(new TransformationFailureListener($this->translator));
        }
    }

    public function getExtendedType()
    {
        return 'Symfony2\Component\Form\Extension\Core\Type\FormType';
    }
}
