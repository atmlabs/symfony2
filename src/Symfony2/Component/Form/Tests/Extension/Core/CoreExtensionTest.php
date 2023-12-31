<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Form\Tests\Extension\Core;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\Form\Extension\Core\CoreExtension;
use Symfony2\Component\Form\FormFactoryBuilder;

class CoreExtensionTest extends TestCase
{
    public function testTransformationFailuresAreConvertedIntoFormErrors()
    {
        $formFactoryBuilder = new FormFactoryBuilder();
        $formFactory = $formFactoryBuilder->addExtension(new CoreExtension())
            ->getFormFactory();

        $form = $formFactory->createBuilder()
            ->add('foo', 'Symfony2\Component\Form\Extension\Core\Type\DateType')
            ->getForm();
        $form->submit('foo');

        $this->assertFalse($form->isValid());
    }
}
