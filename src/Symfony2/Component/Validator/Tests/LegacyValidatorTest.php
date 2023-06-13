<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Validator\Tests;

use Symfony2\Component\Translation\IdentityTranslator;
use Symfony2\Component\Validator\Constraints\Valid;
use Symfony2\Component\Validator\ConstraintValidatorFactory;
use Symfony2\Component\Validator\MetadataFactoryInterface;
use Symfony2\Component\Validator\Tests\Fixtures\Entity;
use Symfony2\Component\Validator\Tests\Validator\AbstractLegacyApiTest;
use Symfony2\Component\Validator\Validator as LegacyValidator;

/**
 * @group legacy
 */
class LegacyValidatorTest extends AbstractLegacyApiTest
{
    protected function createValidator(MetadataFactoryInterface $metadataFactory, array $objectInitializers = array())
    {
        $translator = new IdentityTranslator();
        $translator->setLocale('en');

        return new LegacyValidator($metadataFactory, new ConstraintValidatorFactory(), $translator, 'validators', $objectInitializers);
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\ValidatorException
     */
    public function testValidateValueRejectsValid()
    {
        $this->validator->validateValue(new Entity(), new Valid());
    }
}
