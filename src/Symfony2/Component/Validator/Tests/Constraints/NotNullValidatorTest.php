<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Validator\Tests\Constraints;

use Symfony2\Component\Validator\Constraints\NotNull;
use Symfony2\Component\Validator\Constraints\NotNullValidator;
use Symfony2\Component\Validator\Validation;

class NotNullValidatorTest extends AbstractConstraintValidatorTest
{
    protected function getApiVersion()
    {
        return Validation::API_VERSION_2_5;
    }

    protected function createValidator()
    {
        return new NotNullValidator();
    }

    /**
     * @dataProvider getValidValues
     */
    public function testValidValues($value)
    {
        $this->validator->validate($value, new NotNull());

        $this->assertNoViolation();
    }

    public function getValidValues()
    {
        return array(
            array(0),
            array(false),
            array(true),
            array(''),
        );
    }

    public function testNullIsInvalid()
    {
        $constraint = new NotNull(array(
            'message' => 'myMessage',
        ));

        $this->validator->validate(null, $constraint);

        $this->buildViolation('myMessage')
            ->setParameter('{{ value }}', 'null')
            ->setCode(NotNull::IS_NULL_ERROR)
            ->assertRaised();
    }
}
