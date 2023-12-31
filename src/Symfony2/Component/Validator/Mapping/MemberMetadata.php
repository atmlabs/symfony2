<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Validator\Mapping;

use Symfony2\Component\Validator\Constraint;
use Symfony2\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony2\Component\Validator\ValidationVisitorInterface;

/**
 * Stores all metadata needed for validating a class property.
 *
 * The method of accessing the property's value must be specified by subclasses
 * by implementing the {@link newReflectionMember()} method.
 *
 * This class supports serialization and cloning.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @see PropertyMetadataInterface
 */
abstract class MemberMetadata extends ElementMetadata implements PropertyMetadataInterface
{
    /**
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getClassName()} instead.
     */
    public $class;

    /**
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getName()} instead.
     */
    public $name;

    /**
     * @internal This property is public in order to reduce the size of the
     *           class' serialized representation. Do not access it. Use
     *           {@link getPropertyName()} instead.
     */
    public $property;

    /**
     * @var \ReflectionMethod[]|\ReflectionProperty[]
     */
    private $reflMember = array();

    /**
     * @param string $class    The name of the class this member is defined on
     * @param string $name     The name of the member
     * @param string $property The property the member belongs to
     */
    public function __construct($class, $name, $property)
    {
        $this->class = $class;
        $this->name = $name;
        $this->property = $property;
    }

    /**
     * {@inheritdoc}
     *
     * @deprecated since version 2.5, to be removed in 3.0.
     */
    public function accept(ValidationVisitorInterface $visitor, $value, $group, $propertyPath, $propagatedGroup = null)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since Symfony 2.5 and will be removed in 3.0.', E_USER_DEPRECATED);

        $visitor->visit($this, $value, $group, $propertyPath);

        if ($this->isCascaded()) {
            $visitor->validate($value, $propagatedGroup ?: $group, $propertyPath, $this->isCollectionCascaded(), $this->isCollectionCascadedDeeply());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function addConstraint(Constraint $constraint)
    {
        if (!\in_array(Constraint::PROPERTY_CONSTRAINT, (array) $constraint->getTargets())) {
            throw new ConstraintDefinitionException(sprintf('The constraint %s cannot be put on properties or getters', \get_class($constraint)));
        }

        parent::addConstraint($constraint);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function __sleep()
    {
        return array_merge(parent::__sleep(), array(
            'class',
            'name',
            'property',
        ));
    }

    /**
     * Returns the name of the member.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassName()
    {
        return $this->class;
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyName()
    {
        return $this->property;
    }

    /**
     * Returns whether this member is public.
     *
     * @param object|string $objectOrClassName The object or the class name
     *
     * @return bool
     */
    public function isPublic($objectOrClassName)
    {
        return $this->getReflectionMember($objectOrClassName)->isPublic();
    }

    /**
     * Returns whether this member is protected.
     *
     * @param object|string $objectOrClassName The object or the class name
     *
     * @return bool
     */
    public function isProtected($objectOrClassName)
    {
        return $this->getReflectionMember($objectOrClassName)->isProtected();
    }

    /**
     * Returns whether this member is private.
     *
     * @param object|string $objectOrClassName The object or the class name
     *
     * @return bool
     */
    public function isPrivate($objectOrClassName)
    {
        return $this->getReflectionMember($objectOrClassName)->isPrivate();
    }

    /**
     * Returns whether objects stored in this member should be validated.
     *
     * @return bool
     *
     * @deprecated since version 2.5, to be removed in 3.0.
     *             Use {@link getCascadingStrategy()} instead.
     */
    public function isCascaded()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since Symfony 2.5 and will be removed in 3.0. Use the getCascadingStrategy() method instead.', E_USER_DEPRECATED);

        return (bool) ($this->cascadingStrategy & CascadingStrategy::CASCADE);
    }

    /**
     * Returns whether arrays or traversable objects stored in this member
     * should be traversed and validated in each entry.
     *
     * @return bool
     *
     * @deprecated since version 2.5, to be removed in 3.0.
     *             Use {@link getTraversalStrategy()} instead.
     */
    public function isCollectionCascaded()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since Symfony 2.5 and will be removed in 3.0. Use the getTraversalStrategy() method instead.', E_USER_DEPRECATED);

        return (bool) ($this->traversalStrategy & (TraversalStrategy::IMPLICIT | TraversalStrategy::TRAVERSE));
    }

    /**
     * Returns whether arrays or traversable objects stored in this member
     * should be traversed recursively for inner arrays/traversable objects.
     *
     * @return bool
     *
     * @deprecated since version 2.5, to be removed in 3.0.
     *             Use {@link getTraversalStrategy()} instead.
     */
    public function isCollectionCascadedDeeply()
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since Symfony 2.5 and will be removed in 3.0. Use the getTraversalStrategy() method instead.', E_USER_DEPRECATED);

        return !($this->traversalStrategy & TraversalStrategy::STOP_RECURSION);
    }

    /**
     * Returns the reflection instance for accessing the member's value.
     *
     * @param object|string $objectOrClassName The object or the class name
     *
     * @return \ReflectionMethod|\ReflectionProperty The reflection instance
     */
    public function getReflectionMember($objectOrClassName)
    {
        $className = \is_string($objectOrClassName) ? $objectOrClassName : \get_class($objectOrClassName);
        if (!isset($this->reflMember[$className])) {
            $this->reflMember[$className] = $this->newReflectionMember($objectOrClassName);
        }

        return $this->reflMember[$className];
    }

    /**
     * Creates a new reflection instance for accessing the member's value.
     *
     * Must be implemented by subclasses.
     *
     * @param object|string $objectOrClassName The object or the class name
     *
     * @return \ReflectionMethod|\ReflectionProperty The reflection instance
     */
    abstract protected function newReflectionMember($objectOrClassName);
}
