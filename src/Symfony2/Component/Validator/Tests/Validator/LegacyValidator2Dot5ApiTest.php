<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Validator\Tests\Validator;

use Symfony2\Component\Translation\IdentityTranslator;
use Symfony2\Component\Validator\ConstraintValidatorFactory;
use Symfony2\Component\Validator\Context\LegacyExecutionContextFactory;
use Symfony2\Component\Validator\MetadataFactoryInterface;
use Symfony2\Component\Validator\Validator\LegacyValidator;

/**
 * @group legacy
 */
class LegacyValidator2Dot5ApiTest extends Abstract2Dot5ApiTest
{
    protected function createValidator(MetadataFactoryInterface $metadataFactory, array $objectInitializers = array())
    {
        $translator = new IdentityTranslator();
        $translator->setLocale('en');

        $contextFactory = new LegacyExecutionContextFactory($metadataFactory, $translator);
        $validatorFactory = new ConstraintValidatorFactory();

        return new LegacyValidator($contextFactory, $metadataFactory, $validatorFactory, $objectInitializers);
    }
}
