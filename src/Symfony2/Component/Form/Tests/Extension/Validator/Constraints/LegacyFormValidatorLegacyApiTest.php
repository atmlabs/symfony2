<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Form\Tests\Extension\Validator\Constraints;

use Symfony2\Component\Validator\Validation;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @group  legacy
 */
class LegacyFormValidatorLegacyApiTest extends FormValidatorTest
{
    protected function getApiVersion()
    {
        return Validation::API_VERSION_2_5_BC;
    }
}
