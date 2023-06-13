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
class IsNullValidator extends ConstraintValidator
{
    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof IsNull) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\IsNull');
        }

        if (null !== $value) {
            if ($this->context instanceof ExecutionContextInterface) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $this->formatValue($value))
                    ->setCode(IsNull::NOT_NULL_ERROR)
                    ->addViolation();
            } else {
                $this->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $this->formatValue($value))
                    ->setCode(IsNull::NOT_NULL_ERROR)
                    ->addViolation();
            }
        }
    }
}
