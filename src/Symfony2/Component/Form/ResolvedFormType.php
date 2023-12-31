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

use Symfony2\Component\EventDispatcher\EventDispatcher;
use Symfony2\Component\Form\Exception\InvalidArgumentException;
use Symfony2\Component\Form\Exception\UnexpectedTypeException;
use Symfony2\Component\Form\Util\StringUtil;
use Symfony2\Component\OptionsResolver\OptionsResolver;

/**
 * A wrapper for a form type and its extensions.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ResolvedFormType implements ResolvedFormTypeInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $blockPrefix;

    /**
     * @var FormTypeInterface
     */
    private $innerType;

    /**
     * @var FormTypeExtensionInterface[]
     */
    private $typeExtensions;

    /**
     * @var ResolvedFormTypeInterface|null
     */
    private $parent;

    /**
     * @var OptionsResolver
     */
    private $optionsResolver;

    public function __construct(FormTypeInterface $innerType, array $typeExtensions = array(), ResolvedFormTypeInterface $parent = null)
    {
        $fqcn = \get_class($innerType);
        $name = $innerType->getName();
        $hasCustomName = $name !== $fqcn;

        if (method_exists($innerType, 'getBlockPrefix')) {
            $reflector = new \ReflectionMethod($innerType, 'getName');
            $isOldOverwritten = 'Symfony2\Component\Form\AbstractType' !== $reflector->getDeclaringClass()->getName();

            $reflector = new \ReflectionMethod($innerType, 'getBlockPrefix');
            $isNewOverwritten = 'Symfony2\Component\Form\AbstractType' !== $reflector->getDeclaringClass()->getName();

            // Bundles compatible with both 2.3 and 2.8 should implement both methods
            // Anyone else should only override getBlockPrefix() if they actually
            // want to have a different block prefix than the default one
            if ($isOldOverwritten && !$isNewOverwritten) {
                @trigger_error(\get_class($innerType).': The FormTypeInterface::getName() method is deprecated since Symfony 2.8 and will be removed in 3.0. Remove it from your classes. Use getBlockPrefix() if you want to customize the template block prefix. This method will be added to the FormTypeInterface with Symfony 3.0.', E_USER_DEPRECATED);
            }

            $blockPrefix = $innerType->getBlockPrefix();
        } else {
            @trigger_error(\get_class($innerType).': The FormTypeInterface::getBlockPrefix() method will be added in version 3.0. You should extend AbstractType or add it to your implementation.', E_USER_DEPRECATED);

            // Deal with classes that don't extend AbstractType
            // Calculate block prefix from the FQCN by default
            $blockPrefix = $hasCustomName ? $name : StringUtil::fqcnToBlockPrefix($fqcn);
        }

        // As of Symfony 2.8, getName() returns the FQCN by default
        // Otherwise check that the name matches the old naming restrictions
        if ($hasCustomName && !preg_match('/^[a-z0-9_]*$/i', $name)) {
            throw new InvalidArgumentException(sprintf('The "%s" form type name ("%s") is not valid. Names must only contain letters, numbers, and "_".', \get_class($innerType), $name));
        }

        foreach ($typeExtensions as $extension) {
            if (!$extension instanceof FormTypeExtensionInterface) {
                throw new UnexpectedTypeException($extension, 'Symfony2\Component\Form\FormTypeExtensionInterface');
            }
        }

        $this->name = $name;
        $this->blockPrefix = $blockPrefix;
        $this->innerType = $innerType;
        $this->typeExtensions = $typeExtensions;
        $this->parent = $parent;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns the prefix of the template block name for this type.
     *
     * @return string The prefix of the template block name
     */
    public function getBlockPrefix()
    {
        return $this->blockPrefix;
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * {@inheritdoc}
     */
    public function getInnerType()
    {
        return $this->innerType;
    }

    /**
     * {@inheritdoc}
     */
    public function getTypeExtensions()
    {
        return $this->typeExtensions;
    }

    /**
     * {@inheritdoc}
     */
    public function createBuilder(FormFactoryInterface $factory, $name, array $options = array())
    {
        $options = $this->getOptionsResolver()->resolve($options);

        // Should be decoupled from the specific option at some point
        $dataClass = isset($options['data_class']) ? $options['data_class'] : null;

        $builder = $this->newBuilder($name, $dataClass, $factory, $options);
        $builder->setType($this);

        return $builder;
    }

    /**
     * {@inheritdoc}
     */
    public function createView(FormInterface $form, FormView $parent = null)
    {
        return $this->newView($parent);
    }

    /**
     * Configures a form builder for the type hierarchy.
     *
     * @param FormBuilderInterface $builder The builder to configure
     * @param array                $options The options used for the configuration
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (null !== $this->parent) {
            $this->parent->buildForm($builder, $options);
        }

        $this->innerType->buildForm($builder, $options);

        foreach ($this->typeExtensions as $extension) {
            $extension->buildForm($builder, $options);
        }
    }

    /**
     * Configures a form view for the type hierarchy.
     *
     * This method is called before the children of the view are built.
     *
     * @param FormView      $view    The form view to configure
     * @param FormInterface $form    The form corresponding to the view
     * @param array         $options The options used for the configuration
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        if (null !== $this->parent) {
            $this->parent->buildView($view, $form, $options);
        }

        $this->innerType->buildView($view, $form, $options);

        foreach ($this->typeExtensions as $extension) {
            $extension->buildView($view, $form, $options);
        }
    }

    /**
     * Finishes a form view for the type hierarchy.
     *
     * This method is called after the children of the view have been built.
     *
     * @param FormView      $view    The form view to configure
     * @param FormInterface $form    The form corresponding to the view
     * @param array         $options The options used for the configuration
     */
    public function finishView(FormView $view, FormInterface $form, array $options)
    {
        if (null !== $this->parent) {
            $this->parent->finishView($view, $form, $options);
        }

        $this->innerType->finishView($view, $form, $options);

        foreach ($this->typeExtensions as $extension) {
            /* @var FormTypeExtensionInterface $extension */
            $extension->finishView($view, $form, $options);
        }
    }

    /**
     * Returns the configured options resolver used for this type.
     *
     * @return \Symfony2\Component\OptionsResolver\OptionsResolverInterface The options resolver
     */
    public function getOptionsResolver()
    {
        if (null === $this->optionsResolver) {
            if (null !== $this->parent) {
                $this->optionsResolver = clone $this->parent->getOptionsResolver();
            } else {
                $this->optionsResolver = new OptionsResolver();
            }

            $this->innerType->setDefaultOptions($this->optionsResolver);

            if (method_exists($this->innerType, 'configureOptions')) {
                $reflector = new \ReflectionMethod($this->innerType, 'setDefaultOptions');
                $isOldOverwritten = 'Symfony2\Component\Form\AbstractType' !== $reflector->getDeclaringClass()->getName();

                $reflector = new \ReflectionMethod($this->innerType, 'configureOptions');
                $isNewOverwritten = 'Symfony2\Component\Form\AbstractType' !== $reflector->getDeclaringClass()->getName();

                if ($isOldOverwritten && !$isNewOverwritten) {
                    @trigger_error(\get_class($this->innerType).': The FormTypeInterface::setDefaultOptions() method is deprecated since Symfony 2.7 and will be removed in 3.0. Use configureOptions() instead. This method will be added to the FormTypeInterface with Symfony 3.0.', E_USER_DEPRECATED);
                }
            } else {
                @trigger_error(\get_class($this->innerType).': The FormTypeInterface::configureOptions() method will be added in Symfony 3.0. You should extend AbstractType or implement it in your classes.', E_USER_DEPRECATED);
            }

            foreach ($this->typeExtensions as $extension) {
                $extension->setDefaultOptions($this->optionsResolver);

                if (method_exists($extension, 'configureOptions')) {
                    $reflector = new \ReflectionMethod($extension, 'setDefaultOptions');
                    $isOldOverwritten = 'Symfony2\Component\Form\AbstractTypeExtension' !== $reflector->getDeclaringClass()->getName();

                    $reflector = new \ReflectionMethod($extension, 'configureOptions');
                    $isNewOverwritten = 'Symfony2\Component\Form\AbstractTypeExtension' !== $reflector->getDeclaringClass()->getName();

                    if ($isOldOverwritten && !$isNewOverwritten) {
                        @trigger_error(\get_class($extension).': The FormTypeExtensionInterface::setDefaultOptions() method is deprecated since Symfony 2.7 and will be removed in 3.0. Use configureOptions() instead. This method will be added to the FormTypeExtensionInterface with Symfony 3.0.', E_USER_DEPRECATED);
                    }
                } else {
                    @trigger_error(\get_class($this->innerType).': The FormTypeExtensionInterface::configureOptions() method will be added in Symfony 3.0. You should extend AbstractTypeExtension or implement it in your classes.', E_USER_DEPRECATED);
                }
            }
        }

        return $this->optionsResolver;
    }

    /**
     * Creates a new builder instance.
     *
     * Override this method if you want to customize the builder class.
     *
     * @param string               $name      The name of the builder
     * @param string               $dataClass The data class
     * @param FormFactoryInterface $factory   The current form factory
     * @param array                $options   The builder options
     *
     * @return FormBuilderInterface The new builder instance
     */
    protected function newBuilder($name, $dataClass, FormFactoryInterface $factory, array $options)
    {
        if ($this->innerType instanceof ButtonTypeInterface) {
            return new ButtonBuilder($name, $options);
        }

        if ($this->innerType instanceof SubmitButtonTypeInterface) {
            return new SubmitButtonBuilder($name, $options);
        }

        return new FormBuilder($name, $dataClass, new EventDispatcher(), $factory, $options);
    }

    /**
     * Creates a new view instance.
     *
     * Override this method if you want to customize the view class.
     *
     * @param FormView|null $parent The parent view, if available
     *
     * @return FormView A new view instance
     */
    protected function newView(FormView $parent = null)
    {
        return new FormView($parent);
    }
}
