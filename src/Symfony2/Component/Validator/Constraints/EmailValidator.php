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
use Symfony2\Component\Validator\Exception\RuntimeException;
use Symfony2\Component\Validator\Exception\UnexpectedTypeException;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class EmailValidator extends ConstraintValidator
{
    private $isStrict;

    /**
     * @param bool $strict
     */
    public function __construct($strict = false)
    {
        $this->isStrict = $strict;
    }

    /**
     * {@inheritdoc}
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof Email) {
            throw new UnexpectedTypeException($constraint, __NAMESPACE__.'\Email');
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_scalar($value) && !(\is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $value = (string) $value;

        if (null === $constraint->strict) {
            $constraint->strict = $this->isStrict;
        }

        if ($constraint->strict) {
            if (!class_exists('\Egulias\EmailValidator\EmailValidator') || interface_exists('\Egulias\EmailValidator\Validation\EmailValidation')) {
                throw new RuntimeException('Strict email validation requires egulias/email-validator:~1.2');
            }

            $strictValidator = new \Egulias\EmailValidator\EmailValidator();

            if (!$strictValidator->isValid($value, false, true)) {
                if ($this->context instanceof ExecutionContextInterface) {
                    $this->context->buildViolation($constraint->message)
                        ->setParameter('{{ value }}', $this->formatValue($value))
                        ->setCode(Email::INVALID_FORMAT_ERROR)
                        ->addViolation();
                } else {
                    $this->buildViolation($constraint->message)
                        ->setParameter('{{ value }}', $this->formatValue($value))
                        ->setCode(Email::INVALID_FORMAT_ERROR)
                        ->addViolation();
                }

                return;
            }
        } elseif (!preg_match('/^.+\@\S+\.\S+$/', $value)) {
            if ($this->context instanceof ExecutionContextInterface) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $this->formatValue($value))
                    ->setCode(Email::INVALID_FORMAT_ERROR)
                    ->addViolation();
            } else {
                $this->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $this->formatValue($value))
                    ->setCode(Email::INVALID_FORMAT_ERROR)
                    ->addViolation();
            }

            return;
        }

        $host = (string) substr($value, strrpos($value, '@') + 1);

        // Check for host DNS resource records
        if ($constraint->checkMX) {
            if (!$this->checkMX($host)) {
                if ($this->context instanceof ExecutionContextInterface) {
                    $this->context->buildViolation($constraint->message)
                        ->setParameter('{{ value }}', $this->formatValue($value))
                        ->setCode(Email::MX_CHECK_FAILED_ERROR)
                        ->addViolation();
                } else {
                    $this->buildViolation($constraint->message)
                        ->setParameter('{{ value }}', $this->formatValue($value))
                        ->setCode(Email::MX_CHECK_FAILED_ERROR)
                        ->addViolation();
                }
            }

            return;
        }

        if ($constraint->checkHost && !$this->checkHost($host)) {
            if ($this->context instanceof ExecutionContextInterface) {
                $this->context->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $this->formatValue($value))
                    ->setCode(Email::HOST_CHECK_FAILED_ERROR)
                    ->addViolation();
            } else {
                $this->buildViolation($constraint->message)
                    ->setParameter('{{ value }}', $this->formatValue($value))
                    ->setCode(Email::HOST_CHECK_FAILED_ERROR)
                    ->addViolation();
            }
        }
    }

    /**
     * Check DNS Records for MX type.
     *
     * @param string $host Host
     *
     * @return bool
     */
    private function checkMX($host)
    {
        return '' !== $host && checkdnsrr($host, 'MX');
    }

    /**
     * Check if one of MX, A or AAAA DNS RR exists.
     *
     * @param string $host Host
     *
     * @return bool
     */
    private function checkHost($host)
    {
        return '' !== $host && ($this->checkMX($host) || (checkdnsrr($host, 'A') || checkdnsrr($host, 'AAAA')));
    }
}
