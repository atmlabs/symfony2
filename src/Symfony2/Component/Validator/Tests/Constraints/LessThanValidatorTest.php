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

use Symfony2\Component\Validator\Constraints\LessThan;
use Symfony2\Component\Validator\Constraints\LessThanValidator;
use Symfony2\Component\Validator\Validation;

/**
 * @author Daniel Holmes <daniel@danielholmes.org>
 */
class LessThanValidatorTest extends AbstractComparisonValidatorTestCase
{
    protected function getApiVersion()
    {
        return Validation::API_VERSION_2_5;
    }

    protected function createValidator()
    {
        return new LessThanValidator();
    }

    protected function createConstraint(array $options = null)
    {
        return new LessThan($options);
    }

    protected function getErrorCode()
    {
        return LessThan::TOO_HIGH_ERROR;
    }

    /**
     * {@inheritdoc}
     */
    public function provideValidComparisons()
    {
        return array(
            array(1, 2),
            array(new \DateTime('2000-01-01'), new \DateTime('2010-01-01')),
            array(new \DateTime('2000-01-01'), '2010-01-01'),
            array(new \DateTime('2000-01-01 UTC'), '2010-01-01 UTC'),
            array(new ComparisonTest_Class(4), new ComparisonTest_Class(5)),
            array('22', '333'),
            array(null, 1),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function provideInvalidComparisons()
    {
        return array(
            array(3, '3', 2, '2', 'integer'),
            array(2, '2', 2, '2', 'integer'),
            array(new \DateTime('2010-01-01'), 'Jan 1, 2010, 12:00 AM', new \DateTime('2000-01-01'), 'Jan 1, 2000, 12:00 AM', 'DateTime'),
            array(new \DateTime('2000-01-01'), 'Jan 1, 2000, 12:00 AM', new \DateTime('2000-01-01'), 'Jan 1, 2000, 12:00 AM', 'DateTime'),
            array(new \DateTime('2010-01-01'), 'Jan 1, 2010, 12:00 AM', '2000-01-01', 'Jan 1, 2000, 12:00 AM', 'DateTime'),
            array(new \DateTime('2000-01-01'), 'Jan 1, 2000, 12:00 AM', '2000-01-01', 'Jan 1, 2000, 12:00 AM', 'DateTime'),
            array(new \DateTime('2010-01-01 UTC'), 'Jan 1, 2010, 12:00 AM', '2000-01-01 UTC', 'Jan 1, 2000, 12:00 AM', 'DateTime'),
            array(new \DateTime('2000-01-01 UTC'), 'Jan 1, 2000, 12:00 AM', '2000-01-01 UTC', 'Jan 1, 2000, 12:00 AM', 'DateTime'),
            array(new ComparisonTest_Class(5), '5', new ComparisonTest_Class(5), '5', __NAMESPACE__.'\ComparisonTest_Class'),
            array(new ComparisonTest_Class(6), '6', new ComparisonTest_Class(5), '5', __NAMESPACE__.'\ComparisonTest_Class'),
            array('333', '"333"', '22', '"22"', 'string'),
        );
    }
}
