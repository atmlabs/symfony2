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

use Symfony2\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony2\Component\PropertyAccess\PropertyAccess;
use Symfony2\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony2\Component\PropertyAccess\PropertyPath;
use Symfony2\Component\Validator\Constraint;
use Symfony2\Component\Validator\ConstraintValidator;
use Symfony2\Component\Validator\Context\ExecutionContextInterface;
use Symfony2\Component\Validator\Exception\RuntimeException;
use Symfony2\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @author Fabien Potencier <fabien@symfony.com>
 * @author Bernhard Schussek <bschussek@symfony.com>
 */
class ExpressionValidator extends ConstraintValidator
{
    private $propertyAccessor;
    private $expressionLanguage;

    public function __construct(PropertyAccessorInterface $propertyAccessor = null, ExpressionLanguage $expressionLanguage = null)
    {
        $this->propertyAccessor = $propertyAccessor;
        $this->expressionLanguage = $expressionLanguage;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Expression) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\Expression');
        }

        $variables = array();

        // Symfony 2.5+
        if ($this->context instanceof ExecutionContextInterface) {
            $variables['value'] = $value;
            $variables['this'] = $this->context->getObject();
        } elseif (null === $this->context->getPropertyName()) {
            $variables['value'] = $value;
            $variables['this'] = $value;
        } else {
            $root = $this->context->getRoot();
            $variables['value'] = $value;

            if (\is_object($root)) {
                // Extract the object that the property belongs to from the object
                // graph
                $path = new PropertyPath($this->context->getPropertyPath());
                $parentPath = $path->getParent();
                $variables['this'] = $parentPath ? $this->getPropertyAccessor()->getValue($root, $parentPath) : $root;
            } else {
                $variables['this'] = null;
            }
        }

        if (!$this->getExpressionLanguage()->evaluate($constraint->expression, $variables)) {
            if ($this->context instanceof ExecutionContextInterface) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $this->formatValue($value, self::OBJECT_TO_STRING))
                    ->setCode(Expression::EXPRESSION_FAILED_ERROR)
                    ->addViolation();
            } else {
                $this->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $this->formatValue($value, self::OBJECT_TO_STRING))
                    ->setCode(Expression::EXPRESSION_FAILED_ERROR)
                    ->addViolation();
            }
        }
    }

    private function getExpressionLanguage()
    {
        if (null === $this->expressionLanguage) {
            if (!class_exists('Symfony2\Component\ExpressionLanguage\ExpressionLanguage')) {
                throw new RuntimeException('Unable to use expressions as the Symfony ExpressionLanguage component is not installed.');
            }
            $this->expressionLanguage = new ExpressionLanguage();
        }

        return $this->expressionLanguage;
    }

    private function getPropertyAccessor()
    {
        if (null === $this->propertyAccessor) {
            if (!class_exists('Symfony2\Component\PropertyAccess\PropertyAccess')) {
                throw new RuntimeException('Unable to use expressions as the Symfony PropertyAccess component is not installed.');
            }
            $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
