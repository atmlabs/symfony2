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

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface FormFactoryInterface
{
    /**
     * Returns a form.
     *
     * @see createBuilder()
     *
     * @param string|FormTypeInterface $type    The type of the form
     * @param mixed                    $data    The initial data
     * @param array                    $options The options
     *
     * @return FormInterface The form named after the type
     *
     * @throws \Symfony2\Component\OptionsResolver\Exception\InvalidOptionsException if any given option is not applicable to the given type
     */
    public function create($type = 'Symfony2\Component\Form\Extension\Core\Type\FormType', $data = null, array $options = array());

    /**
     * Returns a form.
     *
     * @see createNamedBuilder()
     *
     * @param string|int               $name    The name of the form
     * @param string|FormTypeInterface $type    The type of the form
     * @param mixed                    $data    The initial data
     * @param array                    $options The options
     *
     * @return FormInterface The form
     *
     * @throws \Symfony2\Component\OptionsResolver\Exception\InvalidOptionsException if any given option is not applicable to the given type
     */
    public function createNamed($name, $type = 'Symfony2\Component\Form\Extension\Core\Type\FormType', $data = null, array $options = array());

    /**
     * Returns a form for a property of a class.
     *
     * @see createBuilderForProperty()
     *
     * @param string $class    The fully qualified class name
     * @param string $property The name of the property to guess for
     * @param mixed  $data     The initial data
     * @param array  $options  The options for the builder
     *
     * @return FormInterface The form named after the property
     *
     * @throws \Symfony2\Component\OptionsResolver\Exception\InvalidOptionsException if any given option is not applicable to the form type
     */
    public function createForProperty($class, $property, $data = null, array $options = array());

    /**
     * Returns a form builder.
     *
     * @param string|FormTypeInterface $type    The type of the form
     * @param mixed                    $data    The initial data
     * @param array                    $options The options
     *
     * @return FormBuilderInterface The form builder
     *
     * @throws \Symfony2\Component\OptionsResolver\Exception\InvalidOptionsException if any given option is not applicable to the given type
     */
    public function createBuilder($type = 'Symfony2\Component\Form\Extension\Core\Type\FormType', $data = null, array $options = array());

    /**
     * Returns a form builder.
     *
     * @param string|int               $name    The name of the form
     * @param string|FormTypeInterface $type    The type of the form
     * @param mixed                    $data    The initial data
     * @param array                    $options The options
     *
     * @return FormBuilderInterface The form builder
     *
     * @throws \Symfony2\Component\OptionsResolver\Exception\InvalidOptionsException if any given option is not applicable to the given type
     */
    public function createNamedBuilder($name, $type = 'Symfony2\Component\Form\Extension\Core\Type\FormType', $data = null, array $options = array());

    /**
     * Returns a form builder for a property of a class.
     *
     * If any of the 'max_length', 'required' and type options can be guessed,
     * and are not provided in the options argument, the guessed value is used.
     *
     * @param string $class    The fully qualified class name
     * @param string $property The name of the property to guess for
     * @param mixed  $data     The initial data
     * @param array  $options  The options for the builder
     *
     * @return FormBuilderInterface The form builder named after the property
     *
     * @throws \Symfony2\Component\OptionsResolver\Exception\InvalidOptionsException if any given option is not applicable to the form type
     */
    public function createBuilderForProperty($class, $property, $data = null, array $options = array());
}
