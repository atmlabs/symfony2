<?php

namespace Symfony2\Component\Form\Tests\Fixtures;

use Symfony2\Component\Form\AbstractType;
use Symfony2\Component\Form\FormBuilderInterface;
use Symfony2\Component\OptionsResolver\OptionsResolver;

class AuthorType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName')
            ->add('lastName')
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Symfony\Component\Form\Tests\Fixtures\Author',
        ));
    }
}
