<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Validator\Tests\Fixtures;

use Symfony2\Component\Validator\Constraint;
use Symfony2\Component\Validator\ConstraintValidator;
use Symfony2\Component\Validator\ExecutionContextInterface;

class ConstraintAValidator extends ConstraintValidator
{
    public static $passedContext;

    public function initialize(ExecutionContextInterface $context)
    {
        parent::initialize($context);

        self::$passedContext = $context;
    }

    public function validate($value, Constraint $constraint)
    {
        if ('VALID' != $value) {
            $this->context->addViolation('message', array('param' => 'value'));

            return;
        }
    }
}
