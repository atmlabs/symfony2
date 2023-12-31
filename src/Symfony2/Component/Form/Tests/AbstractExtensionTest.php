<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Form\Tests;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\Form\AbstractExtension;
use Symfony2\Component\Form\Tests\Fixtures\FooType;

class AbstractExtensionTest extends TestCase
{
    public function testHasType()
    {
        $loader = new ConcreteExtension();
        $this->assertTrue($loader->hasType('Symfony2\Component\Form\Tests\Fixtures\FooType'));
        $this->assertFalse($loader->hasType('foo'));
    }

    public function testGetType()
    {
        $loader = new ConcreteExtension();
        $this->assertInstanceOf('Symfony2\Component\Form\Tests\Fixtures\FooType', $loader->getType('Symfony2\Component\Form\Tests\Fixtures\FooType'));
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionMessage Custom resolver "Symfony2\Component\Form\Tests\Fixtures\CustomOptionsResolver" must extend "Symfony2\Component\OptionsResolver\OptionsResolver".
     */
    public function testCustomOptionsResolver()
    {
        $extension = new Fixtures\LegacyFooTypeBarExtension();
        $resolver = new Fixtures\CustomOptionsResolver();
        $extension->setDefaultOptions($resolver);
    }
}

class ConcreteExtension extends AbstractExtension
{
    protected function loadTypes()
    {
        return array(new FooType());
    }

    protected function loadTypeGuesser()
    {
    }
}
