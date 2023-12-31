<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Validator\Constraints;

use Symfony2\Component\Validator\Constraint;
use Symfony2\Component\Validator\ConstraintValidator;
use Symfony2\Component\Validator\Context\ExecutionContextInterface;
use Symfony2\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class CollectionValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Collection) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\Collection');
        }

        if (null === $value) {
            return;
        }

        if (!\is_array($value) && !($value instanceof \Traversable && $value instanceof \ArrayAccess)) {
            throw new UnexpectedTypeException($value, 'array or Traversable and ArrayAccess');
        }

        // We need to keep the initialized context when CollectionValidator
        // calls itself recursively (Collection constraints can be nested).
        // Since the context of the validator is overwritten when initialize()
        // is called for the nested constraint, the outer validator is
        // acting on the wrong context when the nested validation terminates.
        //
        // A better solution - which should be approached in Symfony 3.0 - is to
        // remove the initialize() method and pass the context as last argument
        // to validate() instead.
        $context = $this->context;

        foreach ($constraint->fields as $field => $fieldConstraint) {
            // bug fix issue #2779
            $existsInArray = \is_array($value) && array_key_exists($field, $value);
            $existsInArrayAccess = $value instanceof \ArrayAccess && $value->offsetExists($field);

            if ($existsInArray || $existsInArrayAccess) {
                if (\count($fieldConstraint->constraints) > 0) {
                    if ($context instanceof ExecutionContextInterface) {
                        $context->getValidator()
                            ->inContext($context)
                            ->atPath('['.$field.']')
                            ->validate($value[$field], $fieldConstraint->constraints);
                    } else {
                        // 2.4 API
                        $context->validateValue($value[$field], $fieldConstraint->constraints, '['.$field.']');
                    }
                }
            } elseif (!$fieldConstraint instanceof Optional && !$constraint->allowMissingFields) {
                if ($context instanceof ExecutionContextInterface) {
                    $context->buildViolation($constraint->missingFieldsMessage)
                        ->atPath('['.$field.']')
                        ->setParameter('{{ field }}', $this->formatValue($field))
                        ->setInvalidValue(null)
                        ->setCode(Collection::MISSING_FIELD_ERROR)
                        ->addViolation();
                } else {
                    $this->buildViolationInContext($context, $constraint->missingFieldsMessage)
                        ->atPath('['.$field.']')
                        ->setParameter('{{ field }}', $this->formatValue($field))
                        ->setInvalidValue(null)
                        ->setCode(Collection::MISSING_FIELD_ERROR)
                        ->addViolation();
                }
            }
        }

        if (!$constraint->allowExtraFields) {
            foreach ($value as $field => $fieldValue) {
                if (!isset($constraint->fields[$field])) {
                    if ($context instanceof ExecutionContextInterface) {
                        $context->buildViolation($constraint->extraFieldsMessage)
                            ->atPath('['.$field.']')
                            ->setParameter('{{ field }}', $this->formatValue($field))
                            ->setInvalidValue($fieldValue)
                            ->setCode(Collection::NO_SUCH_FIELD_ERROR)
                            ->addViolation();
                    } else {
                        $this->buildViolationInContext($context, $constraint->extraFieldsMessage)
                            ->atPath('['.$field.']')
                            ->setParameter('{{ field }}', $this->formatValue($field))
                            ->setInvalidValue($fieldValue)
                            ->setCode(Collection::NO_SUCH_FIELD_ERROR)
                            ->addViolation();
                    }
                }
            }
        }
    }
}
