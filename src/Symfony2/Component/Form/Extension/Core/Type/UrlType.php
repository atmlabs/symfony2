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

use Symfony2\Component\Form\AbstractType;
use Symfony2\Component\Form\Extension\Core\EventListener\FixUrlProtocolListener;
use Symfony2\Component\Form\FormBuilderInterface;
use Symfony2\Component\OptionsResolver\OptionsResolver;

class UrlType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (null !== $options['default_protocol']) {
            $builder->addEventSubscriber(new FixUrlProtocolListener($options['default_protocol']));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefault('default_protocol', 'http');

        $resolver->setAllowedTypes('default_protocol', array('null', 'string'));
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return __NAMESPACE__.'\TextType';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'url';
    }
}
