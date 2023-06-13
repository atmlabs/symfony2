<?php

namespace Symfony2\Component\Form\Tests\Fixtures;

use Symfony2\Component\Form\AbstractType;
use Symfony2\Component\Form\FormBuilderInterface;
use Symfony2\Component\Form\FormEvent;
use Symfony2\Component\Form\FormEvents;

class AlternatingRowType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $form = $event->getForm();
            $type = 0 === $form->getName() % 2
                ? 'Symfony\Component\Form\Extension\Core\Type\TextType'
                : 'Symfony\Component\Form\Extension\Core\Type\TextareaType';
            $form->add('title', $type);
        });
    }
}
