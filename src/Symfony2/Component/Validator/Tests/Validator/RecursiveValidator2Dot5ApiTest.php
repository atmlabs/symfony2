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
use Symfony2\Component\Validator\Context\ExecutionContextFactory;
use Symfony2\Component\Validator\MetadataFactoryInterface;
use Symfony2\Component\Validator\Tests\Fixtures\Entity;
use Symfony2\Component\Validator\Validator\RecursiveValidator;

class RecursiveValidator2Dot5ApiTest extends Abstract2Dot5ApiTest
{
    protected function createValidator(MetadataFactoryInterface $metadataFactory, array $objectInitializers = array())
    {
        $translator = new IdentityTranslator();
        $translator->setLocale('en');

        $contextFactory = new ExecutionContextFactory($translator);
        $validatorFactory = new ConstraintValidatorFactory();

        return new RecursiveValidator($contextFactory, $metadataFactory, $validatorFactory, $objectInitializers);
    }

    public function testEmptyGroupsArrayDoesNotTriggerDeprecation()
    {
        $entity = new Entity();

        $validatorContext = $this->getMockBuilder('Symfony\Component\Validator\Validator\ContextualValidatorInterface')->getMock();
        $validatorContext
            ->expects($this->once())
            ->method('validate')
            ->with($entity, null, array())
            ->willReturnSelf();

        $validator = $this
            ->getMockBuilder('Symfony\Component\Validator\Validator\RecursiveValidator')
            ->disableOriginalConstructor()
            ->setMethods(array('startContext'))
            ->getMock();
        $validator
            ->expects($this->once())
            ->method('startContext')
            ->willReturn($validatorContext);

        $validator->validate($entity, null, array());
    }
}
