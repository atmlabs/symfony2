<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\PropertyInfo\Tests\PhpDocExtractors;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony2\Component\PropertyInfo\Type;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class PhpDocExtractorTest extends TestCase
{
    /**
     * @var PhpDocExtractor
     */
    private $extractor;

    protected function setUp()
    {
        $this->extractor = new PhpDocExtractor();
    }

    /**
     * @dataProvider typesProvider
     */
    public function testExtract($property, array $type = null, $shortDescription, $longDescription)
    {
        $this->assertEquals($type, $this->extractor->getTypes('Symfony2\Component\PropertyInfo\Tests\Fixtures\Dummy', $property));
        $this->assertSame($shortDescription, $this->extractor->getShortDescription('Symfony2\Component\PropertyInfo\Tests\Fixtures\Dummy', $property));
        $this->assertSame($longDescription, $this->extractor->getLongDescription('Symfony2\Component\PropertyInfo\Tests\Fixtures\Dummy', $property));
    }

    public function typesProvider()
    {
        return array(
            array('foo', null, 'Short description.', 'Long description.'),
            array('bar', array(new Type(Type::BUILTIN_TYPE_STRING)), 'This is bar', null),
            array('baz', array(new Type(Type::BUILTIN_TYPE_INT)), 'Should be used.', null),
            array('foo2', array(new Type(Type::BUILTIN_TYPE_FLOAT)), null, null),
            array('foo3', array(new Type(Type::BUILTIN_TYPE_CALLABLE)), null, null),
            array('foo4', array(new Type(Type::BUILTIN_TYPE_NULL)), null, null),
            array('foo5', null, null, null),
            array(
                'files',
                array(
                    new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_OBJECT, false, 'SplFileInfo')),
                    new Type(Type::BUILTIN_TYPE_RESOURCE),
                ),
                null,
                null,
            ),
            array('bal', array(new Type(Type::BUILTIN_TYPE_OBJECT, false, 'DateTime')), null, null),
            array('parent', array(new Type(Type::BUILTIN_TYPE_OBJECT, false, 'Symfony2\Component\PropertyInfo\Tests\Fixtures\ParentDummy')), null, null),
            array('collection', array(new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_OBJECT, false, 'DateTime'))), null, null),
            array('mixedCollection', array(new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, null, null)), null, null),
            array('nestedCollection', array(new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_STRING, false)))), null, null),
            array('a', array(new Type(Type::BUILTIN_TYPE_INT)), 'A.', null),
            array('b', array(new Type(Type::BUILTIN_TYPE_OBJECT, true, 'Symfony2\Component\PropertyInfo\Tests\Fixtures\ParentDummy')), 'B.', null),
            array('c', array(new Type(Type::BUILTIN_TYPE_BOOL, true)), null, null),
            array('d', array(new Type(Type::BUILTIN_TYPE_BOOL)), null, null),
            array('e', array(new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_RESOURCE))), null, null),
            array('f', array(new Type(Type::BUILTIN_TYPE_ARRAY, false, null, true, new Type(Type::BUILTIN_TYPE_INT), new Type(Type::BUILTIN_TYPE_OBJECT, false, 'DateTime'))), null, null),
            array('g', array(new Type(Type::BUILTIN_TYPE_ARRAY, true, null, true)), 'Nullable array.', null),
            array('donotexist', null, null, null),
            array('staticGetter', null, null, null),
            array('staticSetter', null, null, null),
        );
    }

    public function testParamTagTypeIsOmitted()
    {
        $this->assertNull($this->extractor->getTypes('Symfony2\Component\PropertyInfo\Tests\PhpDocExtractors\OmittedParamTagTypeDocBlock', 'omittedType'));
    }
}

class OmittedParamTagTypeDocBlock
{
    /**
     * The type is omitted here to ensure that the extractor doesn't choke on missing types.
     *
     * @param $omittedTagType
     */
    public function setOmittedType(array $omittedTagType)
    {
    }
}
