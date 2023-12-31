<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Validator\Tests\Mapping\Loader;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\Validator\Constraints\All;
use Symfony2\Component\Validator\Constraints\Callback;
use Symfony2\Component\Validator\Constraints\Choice;
use Symfony2\Component\Validator\Constraints\Collection;
use Symfony2\Component\Validator\Constraints\IsTrue;
use Symfony2\Component\Validator\Constraints\NotNull;
use Symfony2\Component\Validator\Constraints\Range;
use Symfony2\Component\Validator\Constraints\Regex;
use Symfony2\Component\Validator\Constraints\Traverse;
use Symfony2\Component\Validator\Exception\MappingException;
use Symfony2\Component\Validator\Mapping\ClassMetadata;
use Symfony2\Component\Validator\Mapping\Loader\XmlFileLoader;
use Symfony2\Component\Validator\Tests\Fixtures\ConstraintA;
use Symfony2\Component\Validator\Tests\Fixtures\ConstraintB;

class XmlFileLoaderTest extends TestCase
{
    public function testLoadClassMetadataReturnsTrueIfSuccessful()
    {
        $loader = new XmlFileLoader(__DIR__.'/constraint-mapping.xml');
        $metadata = new ClassMetadata('Symfony2\Component\Validator\Tests\Fixtures\Entity');

        $this->assertTrue($loader->loadClassMetadata($metadata));
    }

    public function testLoadClassMetadataReturnsFalseIfNotSuccessful()
    {
        $loader = new XmlFileLoader(__DIR__.'/constraint-mapping.xml');
        $metadata = new ClassMetadata('\stdClass');

        $this->assertFalse($loader->loadClassMetadata($metadata));
    }

    public function testLoadClassMetadata()
    {
        $loader = new XmlFileLoader(__DIR__.'/constraint-mapping.xml');
        $metadata = new ClassMetadata('Symfony2\Component\Validator\Tests\Fixtures\Entity');

        $loader->loadClassMetadata($metadata);

        $expected = new ClassMetadata('Symfony2\Component\Validator\Tests\Fixtures\Entity');
        $expected->setGroupSequence(array('Foo', 'Entity'));
        $expected->addConstraint(new ConstraintA());
        $expected->addConstraint(new ConstraintB());
        $expected->addConstraint(new Callback('validateMe'));
        $expected->addConstraint(new Callback('validateMeStatic'));
        $expected->addConstraint(new Callback(array('Symfony2\Component\Validator\Tests\Fixtures\CallbackClass', 'callback')));
        $expected->addConstraint(new Traverse(false));
        $expected->addPropertyConstraint('firstName', new NotNull());
        $expected->addPropertyConstraint('firstName', new Range(array('min' => 3)));
        $expected->addPropertyConstraint('firstName', new Choice(array('A', 'B')));
        $expected->addPropertyConstraint('firstName', new All(array(new NotNull(), new Range(array('min' => 3)))));
        $expected->addPropertyConstraint('firstName', new All(array('constraints' => array(new NotNull(), new Range(array('min' => 3))))));
        $expected->addPropertyConstraint('firstName', new Collection(array('fields' => array(
            'foo' => array(new NotNull(), new Range(array('min' => 3))),
            'bar' => array(new Range(array('min' => 5))),
        ))));
        $expected->addPropertyConstraint('firstName', new Choice(array(
            'message' => 'Must be one of %choices%',
            'choices' => array('A', 'B'),
        )));
        $expected->addGetterConstraint('lastName', new NotNull());
        $expected->addGetterConstraint('valid', new IsTrue());
        $expected->addGetterConstraint('permissions', new IsTrue());

        $this->assertEquals($expected, $metadata);
    }

    public function testLoadClassMetadataWithNonStrings()
    {
        $loader = new XmlFileLoader(__DIR__.'/constraint-mapping-non-strings.xml');
        $metadata = new ClassMetadata('Symfony2\Component\Validator\Tests\Fixtures\Entity');

        $loader->loadClassMetadata($metadata);

        $expected = new ClassMetadata('Symfony2\Component\Validator\Tests\Fixtures\Entity');
        $expected->addPropertyConstraint('firstName', new Regex(array('pattern' => '/^1/', 'match' => false)));

        $properties = $metadata->getPropertyMetadata('firstName');
        $constraints = $properties[0]->getConstraints();

        $this->assertFalse($constraints[0]->match);
    }

    public function testLoadGroupSequenceProvider()
    {
        $loader = new XmlFileLoader(__DIR__.'/constraint-mapping.xml');
        $metadata = new ClassMetadata('Symfony2\Component\Validator\Tests\Fixtures\GroupSequenceProviderEntity');

        $loader->loadClassMetadata($metadata);

        $expected = new ClassMetadata('Symfony2\Component\Validator\Tests\Fixtures\GroupSequenceProviderEntity');
        $expected->setGroupSequenceProvider(true);

        $this->assertEquals($expected, $metadata);
    }

    public function testThrowExceptionIfDocTypeIsSet()
    {
        $loader = new XmlFileLoader(__DIR__.'/withdoctype.xml');
        $metadata = new ClassMetadata('Symfony2\Component\Validator\Tests\Fixtures\Entity');

        $this->{method_exists($this, $_ = 'expectException') ? $_ : 'setExpectedException'}('\Symfony2\Component\Validator\Exception\MappingException');
        $loader->loadClassMetadata($metadata);
    }

    /**
     * @see https://github.com/symfony/symfony/pull/12158
     */
    public function testDoNotModifyStateIfExceptionIsThrown()
    {
        $loader = new XmlFileLoader(__DIR__.'/withdoctype.xml');
        $metadata = new ClassMetadata('Symfony2\Component\Validator\Tests\Fixtures\Entity');

        try {
            $loader->loadClassMetadata($metadata);
        } catch (MappingException $e) {
            $this->{method_exists($this, $_ = 'expectException') ? $_ : 'setExpectedException'}('\Symfony2\Component\Validator\Exception\MappingException');
            $loader->loadClassMetadata($metadata);
        }
    }
}
