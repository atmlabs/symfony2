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

use Symfony2\Component\Intl\Util\IntlTestHelper;
use Symfony2\Component\Validator\Constraints\Country;
use Symfony2\Component\Validator\Constraints\CountryValidator;
use Symfony2\Component\Validator\Validation;

class CountryValidatorTest extends AbstractConstraintValidatorTest
{
    protected function getApiVersion()
    {
        return Validation::API_VERSION_2_5;
    }

    protected function createValidator()
    {
        return new CountryValidator();
    }

    public function testNullIsValid()
    {
        $this->validator->validate(null, new Country());

        $this->assertNoViolation();
    }

    public function testEmptyStringIsValid()
    {
        $this->validator->validate('', new Country());

        $this->assertNoViolation();
    }

    /**
     * @expectedException \Symfony2\Component\Validator\Exception\UnexpectedTypeException
     */
    public function testExpectsStringCompatibleType()
    {
        $this->validator->validate(new \stdClass(), new Country());
    }

    /**
     * @dataProvider getValidCountries
     */
    public function testValidCountries($country)
    {
        $this->validator->validate($country, new Country());

        $this->assertNoViolation();
    }

    public function getValidCountries()
    {
        return array(
            array('GB'),
            array('AT'),
            array('MY'),
        );
    }

    /**
     * @dataProvider getInvalidCountries
     */
    public function testInvalidCountries($country)
    {
        $constraint = new Country(array(
            'message' => 'myMessage',
        ));

        $this->validator->validate($country, $constraint);

        $this->buildViolation('myMessage')
            ->setParameter('{{ value }}', '"'.$country.'"')
            ->setCode(Country::NO_SUCH_COUNTRY_ERROR)
            ->assertRaised();
    }

    public function getInvalidCountries()
    {
        return array(
            array('foobar'),
            array('EN'),
        );
    }

    public function testValidateUsingCountrySpecificLocale()
    {
        // in order to test with "en_GB"
        IntlTestHelper::requireFullIntl($this, false);

        \Locale::setDefault('en_GB');

        $existingCountry = 'GB';

        $this->validator->validate($existingCountry, new Country());

        $this->assertNoViolation();
    }
}
