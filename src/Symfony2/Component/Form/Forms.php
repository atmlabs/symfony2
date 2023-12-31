<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Form;

use Symfony2\Component\Form\Extension\Core\CoreExtension;

/**
 * Entry point of the Form component.
 *
 * Use this class to conveniently create new form factories:
 *
 *     use Symfony2\Component\Form\Forms;
 *
 *     $formFactory = Forms::createFormFactory();
 *
 *     $form = $formFactory->createBuilder()
 *         ->add('firstName', 'Symfony2\Component\Form\Extension\Core\Type\TextType')
 *         ->add('lastName', 'Symfony2\Component\Form\Extension\Core\Type\TextType')
 *         ->add('age', 'Symfony2\Component\Form\Extension\Core\Type\IntegerType')
 *         ->add('gender', 'Symfony2\Component\Form\Extension\Core\Type\ChoiceType', array(
 *             'choices' => array('Male' => 'm', 'Female' => 'f'),
 *             'choices_as_values' => true,
 *         ))
 *         ->getForm();
 *
 * You can also add custom extensions to the form factory:
 *
 *     $formFactory = Forms::createFormFactoryBuilder()
 *         ->addExtension(new AcmeExtension())
 *         ->getFormFactory();
 *
 * If you create custom form types or type extensions, it is
 * generally recommended to create your own extensions that lazily
 * load these types and type extensions. In projects where performance
 * does not matter that much, you can also pass them directly to the
 * form factory:
 *
 *     $formFactory = Forms::createFormFactoryBuilder()
 *         ->addType(new PersonType())
 *         ->addType(new PhoneNumberType())
 *         ->addTypeExtension(new FormTypeHelpTextExtension())
 *         ->getFormFactory();
 *
 * Support for the Validator component is provided by ValidatorExtension.
 * This extension needs a validator object to function properly:
 *
 *     use Symfony2\Component\Validator\Validation;
 *     use Symfony2\Component\Form\Extension\Validator\ValidatorExtension;
 *
 *     $validator = Validation::createValidator();
 *     $formFactory = Forms::createFormFactoryBuilder()
 *         ->addExtension(new ValidatorExtension($validator))
 *         ->getFormFactory();
 *
 * Support for the Templating component is provided by TemplatingExtension.
 * This extension needs a PhpEngine object for rendering forms. As second
 * argument you should pass the names of the default themes. Here is an
 * example for using the default layout with "<div>" tags:
 *
 *     use Symfony2\Component\Form\Extension\Templating\TemplatingExtension;
 *
 *     $formFactory = Forms::createFormFactoryBuilder()
 *         ->addExtension(new TemplatingExtension($engine, null, array(
 *             'FrameworkBundle:Form',
 *         )))
 *         ->getFormFactory();
 *
 * The next example shows how to include the "<table>" layout:
 *
 *     use Symfony2\Component\Form\Extension\Templating\TemplatingExtension;
 *
 *     $formFactory = Forms::createFormFactoryBuilder()
 *         ->addExtension(new TemplatingExtension($engine, null, array(
 *             'FrameworkBundle:Form',
 *             'FrameworkBundle:FormTable',
 *         )))
 *         ->getFormFactory();
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
final class Forms
{
    /**
     * Creates a form factory with the default configuration.
     *
     * @return FormFactoryInterface The form factory
     */
    public static function createFormFactory()
    {
        return self::createFormFactoryBuilder()->getFormFactory();
    }

    /**
     * Creates a form factory builder with the default configuration.
     *
     * @return FormFactoryBuilderInterface The form factory builder
     */
    public static function createFormFactoryBuilder()
    {
        $builder = new FormFactoryBuilder();
        $builder->addExtension(new CoreExtension());

        return $builder;
    }

    /**
     * This class cannot be instantiated.
     */
    private function __construct()
    {
    }
}
