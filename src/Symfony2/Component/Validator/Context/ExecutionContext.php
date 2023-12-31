<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Validator\Context;

use Symfony2\Component\Translation\TranslatorInterface;
use Symfony2\Component\Validator\ClassBasedInterface;
use Symfony2\Component\Validator\Constraint;
use Symfony2\Component\Validator\Constraints\Valid;
use Symfony2\Component\Validator\ConstraintViolation;
use Symfony2\Component\Validator\ConstraintViolationList;
use Symfony2\Component\Validator\Mapping\MetadataInterface;
use Symfony2\Component\Validator\Mapping\PropertyMetadataInterface;
use Symfony2\Component\Validator\Util\PropertyPath;
use Symfony2\Component\Validator\Validator\ValidatorInterface;
use Symfony2\Component\Validator\ValidatorInterface as LegacyValidatorInterface;
use Symfony2\Component\Validator\Violation\ConstraintViolationBuilder;

/**
 * The context used and created by {@link ExecutionContextFactory}.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @see ExecutionContextInterface
 *
 * @internal You should not instantiate or use this class. Code against
 *           {@link ExecutionContextInterface} instead.
 */
class ExecutionContext implements ExecutionContextInterface
{
    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * The root value of the validated object graph.
     *
     * @var mixed
     */
    private $root;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string
     */
    private $translationDomain;

    /**
     * The violations generated in the current context.
     *
     * @var ConstraintViolationList
     */
    private $violations;

    /**
     * The currently validated value.
     *
     * @var mixed
     */
    private $value;

    /**
     * The currently validated object.
     *
     * @var object|null
     */
    private $object;

    /**
     * The property path leading to the current value.
     *
     * @var string
     */
    private $propertyPath = '';

    /**
     * The current validation metadata.
     *
     * @var MetadataInterface|null
     */
    private $metadata;

    /**
     * The currently validated group.
     *
     * @var string|null
     */
    private $group;

    /**
     * The currently validated constraint.
     *
     * @var Constraint|null
     */
    private $constraint;

    /**
     * Stores which objects have been validated in which group.
     *
     * @var array
     */
    private $validatedObjects = array();

    /**
     * Stores which class constraint has been validated for which object.
     *
     * @var array
     */
    private $validatedConstraints = array();

    /**
     * Stores which objects have been initialized.
     *
     * @var array
     */
    private $initializedObjects;

    /**
     * Creates a new execution context.
     *
     * @param ValidatorInterface  $validator         The validator
     * @param mixed               $root              The root value of the
     *                                               validated object graph
     * @param TranslatorInterface $translator        The translator
     * @param string|null         $translationDomain The translation domain to
     *                                               use for translating
     *                                               violation messages
     *
     * @internal Called by {@link ExecutionContextFactory}. Should not be used
     *           in user code.
     */
    public function __construct(ValidatorInterface $validator, $root, TranslatorInterface $translator, $translationDomain = null)
    {
        $this->validator = $validator;
        $this->root = $root;
        $this->translator = $translator;
        $this->translationDomain = $translationDomain;
        $this->violations = new ConstraintViolationList();
    }

    /**
     * {@inheritdoc}
     */
    public function setNode($value, $object, MetadataInterface $metadata = null, $propertyPath)
    {
        $this->value = $value;
        $this->object = $object;
        $this->metadata = $metadata;
        $this->propertyPath = (string) $propertyPath;
    }

    /**
     * {@inheritdoc}
     */
    public function setGroup($group)
    {
        $this->group = $group;
    }

    /**
     * {@inheritdoc}
     */
    public function setConstraint(Constraint $constraint)
    {
        $this->constraint = $constraint;
    }

    /**
     * {@inheritdoc}
     */
    public function addViolation($message, array $parameters = array(), $invalidValue = null, $plural = null, $code = null)
    {
        // The parameters $invalidValue and following are ignored by the new
        // API, as they are not present in the new interface anymore.
        // You should use buildViolation() instead.
        if (\func_num_args() > 2) {
            @trigger_error('The parameters $invalidValue, $plural and $code in method '.__METHOD__.' are deprecated since Symfony 2.5 and will be removed in 3.0. Use the '.__CLASS__.'::buildViolation method instead.', E_USER_DEPRECATED);

            $this
                ->buildViolation($message, $parameters)
                ->setInvalidValue($invalidValue)
                ->setPlural($plural)
                ->setCode($code)
                ->addViolation()
            ;

            return;
        }

        $this->violations->add(new ConstraintViolation(
            $this->translator->trans($message, $parameters, $this->translationDomain),
            $message,
            $parameters,
            $this->root,
            $this->propertyPath,
            $this->value,
            null,
            null,
            $this->constraint
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function buildViolation($message, array $parameters = array())
    {
        return new ConstraintViolationBuilder(
            $this->violations,
            $this->constraint,
            $message,
            $parameters,
            $this->root,
            $this->propertyPath,
            $this->value,
            $this->translator,
            $this->translationDomain
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getViolations()
    {
        return $this->violations;
    }

    /**
     * {@inheritdoc}
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * {@inheritdoc}
     */
    public function getRoot()
    {
        return $this->root;
    }

    /**
     * {@inheritdoc}
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * {@inheritdoc}
     */
    public function getObject()
    {
        return $this->object;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * {@inheritdoc}
     */
    public function getGroup()
    {
        return $this->group;
    }

    public function getConstraint()
    {
        return $this->constraint;
    }

    /**
     * {@inheritdoc}
     */
    public function getClassName()
    {
        return $this->metadata instanceof ClassBasedInterface ? $this->metadata->getClassName() : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyName()
    {
        return $this->metadata instanceof PropertyMetadataInterface ? $this->metadata->getPropertyName() : null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPropertyPath($subPath = '')
    {
        return PropertyPath::append($this->propertyPath, $subPath);
    }

    /**
     * {@inheritdoc}
     */
    public function addViolationAt($subPath, $message, array $parameters = array(), $invalidValue = null, $plural = null, $code = null)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since Symfony 2.5 and will be removed in 3.0. Use the '.__CLASS__.'::buildViolation method instead.', E_USER_DEPRECATED);

        if (\func_num_args() > 2) {
            $this
                ->buildViolation($message, $parameters)
                ->atPath($subPath)
                ->setInvalidValue($invalidValue)
                ->setPlural($plural)
                ->setCode($code)
                ->addViolation()
            ;

            return;
        }

        $this
            ->buildViolation($message, $parameters)
            ->atPath($subPath)
            ->addViolation()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, $subPath = '', $groups = null, $traverse = false, $deep = false)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since Symfony 2.5 and will be removed in 3.0. Use the '.__CLASS__.'::getValidator() method instead.', E_USER_DEPRECATED);

        if (\is_array($value)) {
            // The $traverse flag is ignored for arrays
            $constraint = new Valid(array('traverse' => true, 'deep' => $deep));

            return $this
                ->getValidator()
                ->inContext($this)
                ->atPath($subPath)
                ->validate($value, $constraint, $groups)
            ;
        }

        if ($traverse && $value instanceof \Traversable) {
            $constraint = new Valid(array('traverse' => true, 'deep' => $deep));

            return $this
                ->getValidator()
                ->inContext($this)
                ->atPath($subPath)
                ->validate($value, $constraint, $groups)
            ;
        }

        return $this
            ->getValidator()
            ->inContext($this)
            ->atPath($subPath)
            ->validate($value, null, $groups)
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function validateValue($value, $constraints, $subPath = '', $groups = null)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since Symfony 2.5 and will be removed in 3.0. Use the '.__CLASS__.'::getValidator() method instead.', E_USER_DEPRECATED);

        return $this
            ->getValidator()
            ->inContext($this)
            ->atPath($subPath)
            ->validate($value, $constraints, $groups)
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadataFactory()
    {
        @trigger_error('The '.__METHOD__.' is deprecated since Symfony 2.5 and will be removed in 3.0. Use the new Symfony2\Component\Validator\Context\ExecutionContext::getValidator method in combination with Symfony2\Component\Validator\Validator\ValidatorInterface::getMetadataFor or Symfony2\Component\Validator\Validator\ValidatorInterface::hasMetadataFor method instead.', E_USER_DEPRECATED);

        $validator = $this->getValidator();

        if ($validator instanceof LegacyValidatorInterface) {
            return $validator->getMetadataFactory();
        }

        // The ValidatorInterface extends from the deprecated MetadataFactoryInterface, so return it when we don't have the factory instance itself
        return $validator;
    }

    /**
     * {@inheritdoc}
     */
    public function markGroupAsValidated($cacheKey, $groupHash)
    {
        if (!isset($this->validatedObjects[$cacheKey])) {
            $this->validatedObjects[$cacheKey] = array();
        }

        $this->validatedObjects[$cacheKey][$groupHash] = true;
    }

    /**
     * {@inheritdoc}
     */
    public function isGroupValidated($cacheKey, $groupHash)
    {
        return isset($this->validatedObjects[$cacheKey][$groupHash]);
    }

    /**
     * {@inheritdoc}
     */
    public function markConstraintAsValidated($cacheKey, $constraintHash)
    {
        $this->validatedConstraints[$cacheKey.':'.$constraintHash] = true;
    }

    /**
     * {@inheritdoc}
     */
    public function isConstraintValidated($cacheKey, $constraintHash)
    {
        return isset($this->validatedConstraints[$cacheKey.':'.$constraintHash]);
    }

    /**
     * {@inheritdoc}
     */
    public function markObjectAsInitialized($cacheKey)
    {
        $this->initializedObjects[$cacheKey] = true;
    }

    /**
     * {@inheritdoc}
     */
    public function isObjectInitialized($cacheKey)
    {
        return isset($this->initializedObjects[$cacheKey]);
    }
}
