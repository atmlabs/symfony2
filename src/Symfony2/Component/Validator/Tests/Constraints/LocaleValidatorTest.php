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

use Symfony2\Component\Validator\Constraints\Locale;
use Symfony2\Component\Validator\Constraints\LocaleValidator;
use Symfony2\Component\Validator\Validation;

class LocaleValidatorTest extends AbstractConstraintValidatorTest
{
    protected function getApiVersion()
    {
        return Validation::API_VERSION_2_5;
    }

    protected function createValidator()
    {
        return new LocaleValidator();
    }

    public function testNullIsValid()
    {
        $this->validator->validate(null, new Locale());

        $this->assertNoViolation();
    }

    public function testEmptyStringIsValid()
    {
        $this->validator->validate('', new Locale());

        $this->assertNoViolation();
    }

    /**
     * @expectedException \Symfony2\Component\Validator\Exception\UnexpectedTypeException
     */
    public function testExpectsStringCompatibleType()
    {
        $this->validator->validate(new \stdClass(), new Locale());
    }

    /**
     * @dataProvider getValidLocales
     */
    public function testValidLocales($locale)
    {
        $this->validator->validate($locale, new Locale());

        $this->assertNoViolation();
    }

    public function getValidLocales()
    {
        return array(
            array('en'),
            array('en_US'),
            array('pt'),
            array('pt_PT'),
            array('zh_Hans'),
            array('fil_PH'),
        );
    }

    /**
     * @dataProvider getInvalidLocales
     */
    public function testInvalidLocales($locale)
    {
        $constraint = new Locale(array(
            'message' => 'myMessage',
        ));

        $this->validator->validate($locale, $constraint);

        $this->buildViolation('myMessage')
            ->setParameter('{{ value }}', '"'.$locale.'"')
            ->setCode(Locale::NO_SUCH_LOCALE_ERROR)
            ->assertRaised();
    }

    public function getInvalidLocales()
    {
        return array(
            array('EN'),
            array('foobar'),
        );
    }
}
