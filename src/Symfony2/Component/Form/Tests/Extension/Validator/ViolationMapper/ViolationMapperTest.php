<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Form\Tests\Extension\Validator\ViolationMapper;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\Form\CallbackTransformer;
use Symfony2\Component\Form\Exception\TransformationFailedException;
use Symfony2\Component\Form\Extension\Validator\ViolationMapper\ViolationMapper;
use Symfony2\Component\Form\Form;
use Symfony2\Component\Form\FormConfigBuilder;
use Symfony2\Component\Form\FormError;
use Symfony2\Component\Form\FormInterface;
use Symfony2\Component\PropertyAccess\PropertyPath;
use Symfony2\Component\Validator\ConstraintViolation;
use Symfony2\Component\Validator\ConstraintViolationInterface;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class ViolationMapperTest extends TestCase
{
    const LEVEL_0 = 0;
    const LEVEL_1 = 1;
    const LEVEL_1B = 2;
    const LEVEL_2 = 3;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $dispatcher;

    /**
     * @var ViolationMapper
     */
    private $mapper;

    /**
     * @var string
     */
    private $message;

    /**
     * @var string
     */
    private $messageTemplate;

    /**
     * @var array
     */
    private $params;

    protected function setUp()
    {
        $this->dispatcher = $this->getMockBuilder('Symfony2\Component\EventDispatcher\EventDispatcherInterface')->getMock();
        $this->mapper = new ViolationMapper();
        $this->message = 'Message';
        $this->messageTemplate = 'Message template';
        $this->params = array('foo' => 'bar');
    }

    protected function getForm($name = 'name', $propertyPath = null, $dataClass = null, $errorMapping = array(), $inheritData = false, $synchronized = true)
    {
        $config = new FormConfigBuilder($name, $dataClass, $this->dispatcher, array(
            'error_mapping' => $errorMapping,
        ));
        $config->setMapped(true);
        $config->setInheritData($inheritData);
        $config->setPropertyPath($propertyPath);
        $config->setCompound(true);
        $config->setDataMapper($this->getDataMapper());

        if (!$synchronized) {
            $config->addViewTransformer(new CallbackTransformer(
                function ($normData) { return $normData; },
                function () { throw new TransformationFailedException(); }
            ));
        }

        return new Form($config);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function getDataMapper()
    {
        return $this->getMockBuilder('Symfony2\Component\Form\DataMapperInterface')->getMock();
    }

    /**
     * @param $propertyPath
     *
     * @return ConstraintViolation
     */
    protected function getConstraintViolation($propertyPath)
    {
        return new ConstraintViolation($this->message, $this->messageTemplate, $this->params, null, $propertyPath, null);
    }

    /**
     * @return FormError
     */
    protected function getFormError(ConstraintViolationInterface $violation, FormInterface $form)
    {
        $error = new FormError($this->message, $this->messageTemplate, $this->params, null, $violation);
        $error->setOrigin($form);

        return $error;
    }

    public function testMapToFormInheritingParentDataIfDataDoesNotMatch()
    {
        $violation = $this->getConstraintViolation('children[address].data.foo');
        $parent = $this->getForm('parent');
        $child = $this->getForm('address', 'address', null, array(), true);
        $grandChild = $this->getForm('street');

        $parent->add($child);
        $child->add($grandChild);

        $parent->submit(array());

        $this->mapper->mapViolation($violation, $parent);

        $this->assertCount(0, $parent->getErrors(), $parent->getName().' should not have an error, but has one');
        $this->assertEquals(array($this->getFormError($violation, $child)), iterator_to_array($child->getErrors()), $child->getName().' should have an error, but has none');
        $this->assertCount(0, $grandChild->getErrors(), $grandChild->getName().' should not have an error, but has one');
    }

    public function testFollowDotRules()
    {
        $violation = $this->getConstraintViolation('data.foo');
        $parent = $this->getForm('parent', null, null, array(
            'foo' => 'address',
        ));
        $child = $this->getForm('address', null, null, array(
            '.' => 'street',
        ));
        $grandChild = $this->getForm('street', null, null, array(
            '.' => 'name',
        ));
        $grandGrandChild = $this->getForm('name');

        $parent->add($child);
        $child->add($grandChild);
        $grandChild->add($grandGrandChild);

        $parent->submit(array());

        $this->mapper->mapViolation($violation, $parent);

        $this->assertCount(0, $parent->getErrors(), $parent->getName().' should not have an error, but has one');
        $this->assertCount(0, $child->getErrors(), $child->getName().' should not have an error, but has one');
        $this->assertCount(0, $grandChild->getErrors(), $grandChild->getName().' should not have an error, but has one');
        $this->assertEquals(array($this->getFormError($violation, $grandGrandChild)), iterator_to_array($grandGrandChild->getErrors()), $grandGrandChild->getName().' should have an error, but has none');
    }

    public function testAbortMappingIfNotSynchronized()
    {
        $violation = $this->getConstraintViolation('children[address].data.street');
        $parent = $this->getForm('parent');
        $child = $this->getForm('address', 'address', null, array(), false, false);
        // even though "street" is synchronized, it should not have any errors
        // due to its parent not being synchronized
        $grandChild = $this->getForm('street', 'street');

        $parent->add($child);
        $child->add($grandChild);

        // invoke the transformer and mark the form unsynchronized
        $parent->submit(array());

        $this->mapper->mapViolation($violation, $parent);

        $this->assertCount(0, $parent->getErrors(), $parent->getName().' should not have an error, but has one');
        $this->assertCount(0, $child->getErrors(), $child->getName().' should not have an error, but has one');
        $this->assertCount(0, $grandChild->getErrors(), $grandChild->getName().' should not have an error, but has one');
    }

    public function testAbortDotRuleMappingIfNotSynchronized()
    {
        $violation = $this->getConstraintViolation('data.address');
        $parent = $this->getForm('parent');
        $child = $this->getForm('address', 'address', null, array(
            '.' => 'street',
        ), false, false);
        // even though "street" is synchronized, it should not have any errors
        // due to its parent not being synchronized
        $grandChild = $this->getForm('street');

        $parent->add($child);
        $child->add($grandChild);

        // invoke the transformer and mark the form unsynchronized
        $parent->submit(array());

        $this->mapper->mapViolation($violation, $parent);

        $this->assertCount(0, $parent->getErrors(), $parent->getName().' should not have an error, but has one');
        $this->assertCount(0, $child->getErrors(), $child->getName().' should not have an error, but has one');
        $this->assertCount(0, $grandChild->getErrors(), $grandChild->getName().' should not have an error, but has one');
    }

    public function testAbortMappingIfNotSubmitted()
    {
        $violation = $this->getConstraintViolation('children[address].data.street');
        $parent = $this->getForm('parent');
        $child = $this->getForm('address', 'address');
        $grandChild = $this->getForm('street', 'street');

        $parent->add($child);
        $child->add($grandChild);

        // Disable automatic submission of missing fields
        $parent->submit(array(), false);
        $child->submit(array(), false);

        // $grandChild is not submitted

        $this->mapper->mapViolation($violation, $parent);

        $this->assertCount(0, $parent->getErrors(), $parent->getName().' should not have an error, but has one');
        $this->assertCount(0, $child->getErrors(), $child->getName().' should not have an error, but has one');
        $this->assertCount(0, $grandChild->getErrors(), $grandChild->getName().' should not have an error, but has one');
    }

    public function testAbortDotRuleMappingIfNotSubmitted()
    {
        $violation = $this->getConstraintViolation('data.address');
        $parent = $this->getForm('parent');
        $child = $this->getForm('address', 'address', null, array(
            '.' => 'street',
        ));
        $grandChild = $this->getForm('street');

        $parent->add($child);
        $child->add($grandChild);

        // Disable automatic submission of missing fields
        $parent->submit(array(), false);
        $child->submit(array(), false);

        // $grandChild is not submitted

        $this->mapper->mapViolation($violation, $parent);

        $this->assertCount(0, $parent->getErrors(), $parent->getName().' should not have an error, but has one');
        $this->assertCount(0, $child->getErrors(), $child->getName().' should not have an error, but has one');
        $this->assertCount(0, $grandChild->getErrors(), $grandChild->getName().' should not have an error, but has one');
    }

    public function provideDefaultTests()
    {
        // The mapping must be deterministic! If a child has the property path "[street]",
        // "data[street]" should be mapped, but "data.street" should not!
        return array(
            // mapping target, child name, its property path, grand child name, its property path, violation path
            array(self::LEVEL_0, 'address', 'address', 'street', 'street', ''),
            array(self::LEVEL_0, 'address', 'address', 'street', 'street', 'data'),

            array(self::LEVEL_2, 'address', 'address', 'street', 'street', 'children[address].children[street].data'),
            array(self::LEVEL_2, 'address', 'address', 'street', 'street', 'children[address].children[street].data.prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'street', 'children[address].data'),
            array(self::LEVEL_2, 'address', 'address', 'street', 'street', 'children[address].data.street'),
            array(self::LEVEL_2, 'address', 'address', 'street', 'street', 'children[address].data.street.prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'street', 'children[address].data[street]'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'street', 'children[address].data[street].prop'),
            array(self::LEVEL_2, 'address', 'address', 'street', 'street', 'data.address.street'),
            array(self::LEVEL_2, 'address', 'address', 'street', 'street', 'data.address.street.prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'street', 'data.address[street]'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'street', 'data.address[street].prop'),
            array(self::LEVEL_0, 'address', 'address', 'street', 'street', 'data[address].street'),
            array(self::LEVEL_0, 'address', 'address', 'street', 'street', 'data[address].street.prop'),
            array(self::LEVEL_0, 'address', 'address', 'street', 'street', 'data[address][street]'),
            array(self::LEVEL_0, 'address', 'address', 'street', 'street', 'data[address][street].prop'),

            array(self::LEVEL_2, 'address', 'address', 'street', '[street]', 'children[address].children[street].data'),
            array(self::LEVEL_2, 'address', 'address', 'street', '[street]', 'children[address].children[street].data.prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[street]', 'children[address].data'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[street]', 'children[address].data.street'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[street]', 'children[address].data.street.prop'),
            array(self::LEVEL_2, 'address', 'address', 'street', '[street]', 'children[address].data[street]'),
            array(self::LEVEL_2, 'address', 'address', 'street', '[street]', 'children[address].data[street].prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[street]', 'data.address.street'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[street]', 'data.address.street.prop'),
            array(self::LEVEL_2, 'address', 'address', 'street', '[street]', 'data.address[street]'),
            array(self::LEVEL_2, 'address', 'address', 'street', '[street]', 'data.address[street].prop'),
            array(self::LEVEL_0, 'address', 'address', 'street', '[street]', 'data[address].street'),
            array(self::LEVEL_0, 'address', 'address', 'street', '[street]', 'data[address].street.prop'),
            array(self::LEVEL_0, 'address', 'address', 'street', '[street]', 'data[address][street]'),
            array(self::LEVEL_0, 'address', 'address', 'street', '[street]', 'data[address][street].prop'),

            array(self::LEVEL_2, 'address', '[address]', 'street', 'street', 'children[address].children[street].data'),
            array(self::LEVEL_2, 'address', '[address]', 'street', 'street', 'children[address].children[street].data.prop'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'street', 'children[address].data'),
            array(self::LEVEL_2, 'address', '[address]', 'street', 'street', 'children[address].data.street'),
            array(self::LEVEL_2, 'address', '[address]', 'street', 'street', 'children[address].data.street.prop'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'street', 'children[address].data[street]'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'street', 'children[address].data[street].prop'),
            array(self::LEVEL_0, 'address', '[address]', 'street', 'street', 'data.address.street'),
            array(self::LEVEL_0, 'address', '[address]', 'street', 'street', 'data.address.street.prop'),
            array(self::LEVEL_0, 'address', '[address]', 'street', 'street', 'data.address[street]'),
            array(self::LEVEL_0, 'address', '[address]', 'street', 'street', 'data.address[street].prop'),
            array(self::LEVEL_2, 'address', '[address]', 'street', 'street', 'data[address].street'),
            array(self::LEVEL_2, 'address', '[address]', 'street', 'street', 'data[address].street.prop'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'street', 'data[address][street]'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'street', 'data[address][street].prop'),

            array(self::LEVEL_2, 'address', '[address]', 'street', '[street]', 'children[address].children[street].data'),
            array(self::LEVEL_2, 'address', '[address]', 'street', '[street]', 'children[address].children[street].data.prop'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[street]', 'children[address].data'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[street]', 'children[address].data.street'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[street]', 'children[address].data.street.prop'),
            array(self::LEVEL_2, 'address', '[address]', 'street', '[street]', 'children[address].data[street]'),
            array(self::LEVEL_2, 'address', '[address]', 'street', '[street]', 'children[address].data[street].prop'),
            array(self::LEVEL_0, 'address', '[address]', 'street', '[street]', 'data.address.street'),
            array(self::LEVEL_0, 'address', '[address]', 'street', '[street]', 'data.address.street.prop'),
            array(self::LEVEL_0, 'address', '[address]', 'street', '[street]', 'data.address[street]'),
            array(self::LEVEL_0, 'address', '[address]', 'street', '[street]', 'data.address[street].prop'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[street]', 'data[address].street'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[street]', 'data[address].street.prop'),
            array(self::LEVEL_2, 'address', '[address]', 'street', '[street]', 'data[address][street]'),
            array(self::LEVEL_2, 'address', '[address]', 'street', '[street]', 'data[address][street].prop'),

            array(self::LEVEL_2, 'address', 'person.address', 'street', 'street', 'children[address].children[street].data'),
            array(self::LEVEL_2, 'address', 'person.address', 'street', 'street', 'children[address].children[street].data.prop'),
            array(self::LEVEL_1, 'address', 'person.address', 'street', 'street', 'children[address].data'),
            array(self::LEVEL_2, 'address', 'person.address', 'street', 'street', 'children[address].data.street'),
            array(self::LEVEL_2, 'address', 'person.address', 'street', 'street', 'children[address].data.street.prop'),
            array(self::LEVEL_1, 'address', 'person.address', 'street', 'street', 'children[address].data[street]'),
            array(self::LEVEL_1, 'address', 'person.address', 'street', 'street', 'children[address].data[street].prop'),
            array(self::LEVEL_2, 'address', 'person.address', 'street', 'street', 'data.person.address.street'),
            array(self::LEVEL_2, 'address', 'person.address', 'street', 'street', 'data.person.address.street.prop'),
            array(self::LEVEL_1, 'address', 'person.address', 'street', 'street', 'data.person.address[street]'),
            array(self::LEVEL_1, 'address', 'person.address', 'street', 'street', 'data.person.address[street].prop'),
            array(self::LEVEL_0, 'address', 'person.address', 'street', 'street', 'data.person[address].street'),
            array(self::LEVEL_0, 'address', 'person.address', 'street', 'street', 'data.person[address].street.prop'),
            array(self::LEVEL_0, 'address', 'person.address', 'street', 'street', 'data.person[address][street]'),
            array(self::LEVEL_0, 'address', 'person.address', 'street', 'street', 'data.person[address][street].prop'),
            array(self::LEVEL_0, 'address', 'person.address', 'street', 'street', 'data[person].address.street'),
            array(self::LEVEL_0, 'address', 'person.address', 'street', 'street', 'data[person].address.street.prop'),
            array(self::LEVEL_0, 'address', 'person.address', 'street', 'street', 'data[person].address[street]'),
            array(self::LEVEL_0, 'address', 'person.address', 'street', 'street', 'data[person].address[street].prop'),
            array(self::LEVEL_0, 'address', 'person.address', 'street', 'street', 'data[person][address].street'),
            array(self::LEVEL_0, 'address', 'person.address', 'street', 'street', 'data[person][address].street.prop'),
            array(self::LEVEL_0, 'address', 'person.address', 'street', 'street', 'data[person][address][street]'),
            array(self::LEVEL_0, 'address', 'person.address', 'street', 'street', 'data[person][address][street].prop'),

            array(self::LEVEL_2, 'address', 'person.address', 'street', '[street]', 'children[address].children[street].data'),
            array(self::LEVEL_2, 'address', 'person.address', 'street', '[street]', 'children[address].children[street].data.prop'),
            array(self::LEVEL_1, 'address', 'person.address', 'street', '[street]', 'children[address].data'),
            array(self::LEVEL_1, 'address', 'person.address', 'street', '[street]', 'children[address].data.street'),
            array(self::LEVEL_1, 'address', 'person.address', 'street', '[street]', 'children[address].data.street.prop'),
            array(self::LEVEL_2, 'address', 'person.address', 'street', '[street]', 'children[address].data[street]'),
            array(self::LEVEL_2, 'address', 'person.address', 'street', '[street]', 'children[address].data[street].prop'),
            array(self::LEVEL_1, 'address', 'person.address', 'street', '[street]', 'data.person.address.street'),
            array(self::LEVEL_1, 'address', 'person.address', 'street', '[street]', 'data.person.address.street.prop'),
            array(self::LEVEL_2, 'address', 'person.address', 'street', '[street]', 'data.person.address[street]'),
            array(self::LEVEL_2, 'address', 'person.address', 'street', '[street]', 'data.person.address[street].prop'),
            array(self::LEVEL_0, 'address', 'person.address', 'street', '[street]', 'data.person[address].street'),
            array(self::LEVEL_0, 'address', 'person.address', 'street', '[street]', 'data.person[address].street.prop'),
            array(self::LEVEL_0, 'address', 'person.address', 'street', '[street]', 'data.person[address][street]'),
            array(self::LEVEL_0, 'address', 'person.address', 'street', '[street]', 'data.person[address][street].prop'),
            array(self::LEVEL_0, 'address', 'person.address', 'street', '[street]', 'data[person].address.street'),
            array(self::LEVEL_0, 'address', 'person.address', 'street', '[street]', 'data[person].address.street.prop'),
            array(self::LEVEL_0, 'address', 'person.address', 'street', '[street]', 'data[person].address[street]'),
            array(self::LEVEL_0, 'address', 'person.address', 'street', '[street]', 'data[person].address[street].prop'),
            array(self::LEVEL_0, 'address', 'person.address', 'street', '[street]', 'data[person][address].street'),
            array(self::LEVEL_0, 'address', 'person.address', 'street', '[street]', 'data[person][address].street.prop'),
            array(self::LEVEL_0, 'address', 'person.address', 'street', '[street]', 'data[person][address][street]'),
            array(self::LEVEL_0, 'address', 'person.address', 'street', '[street]', 'data[person][address][street].prop'),

            array(self::LEVEL_2, 'address', 'person[address]', 'street', 'street', 'children[address].children[street].data'),
            array(self::LEVEL_2, 'address', 'person[address]', 'street', 'street', 'children[address].children[street].data.prop'),
            array(self::LEVEL_1, 'address', 'person[address]', 'street', 'street', 'children[address].data'),
            array(self::LEVEL_2, 'address', 'person[address]', 'street', 'street', 'children[address].data.street'),
            array(self::LEVEL_2, 'address', 'person[address]', 'street', 'street', 'children[address].data.street.prop'),
            array(self::LEVEL_1, 'address', 'person[address]', 'street', 'street', 'children[address].data[street]'),
            array(self::LEVEL_1, 'address', 'person[address]', 'street', 'street', 'children[address].data[street].prop'),
            array(self::LEVEL_0, 'address', 'person[address]', 'street', 'street', 'data.person.address.street'),
            array(self::LEVEL_0, 'address', 'person[address]', 'street', 'street', 'data.person.address.street.prop'),
            array(self::LEVEL_0, 'address', 'person[address]', 'street', 'street', 'data.person.address[street]'),
            array(self::LEVEL_0, 'address', 'person[address]', 'street', 'street', 'data.person.address[street].prop'),
            array(self::LEVEL_2, 'address', 'person[address]', 'street', 'street', 'data.person[address].street'),
            array(self::LEVEL_2, 'address', 'person[address]', 'street', 'street', 'data.person[address].street.prop'),
            array(self::LEVEL_1, 'address', 'person[address]', 'street', 'street', 'data.person[address][street]'),
            array(self::LEVEL_1, 'address', 'person[address]', 'street', 'street', 'data.person[address][street].prop'),
            array(self::LEVEL_0, 'address', 'person[address]', 'street', 'street', 'data[person].address.street'),
            array(self::LEVEL_0, 'address', 'person[address]', 'street', 'street', 'data[person].address.street.prop'),
            array(self::LEVEL_0, 'address', 'person[address]', 'street', 'street', 'data[person].address[street]'),
            array(self::LEVEL_0, 'address', 'person[address]', 'street', 'street', 'data[person].address[street].prop'),
            array(self::LEVEL_0, 'address', 'person[address]', 'street', 'street', 'data[person][address].street'),
            array(self::LEVEL_0, 'address', 'person[address]', 'street', 'street', 'data[person][address].street.prop'),
            array(self::LEVEL_0, 'address', 'person[address]', 'street', 'street', 'data[person][address][street]'),
            array(self::LEVEL_0, 'address', 'person[address]', 'street', 'street', 'data[person][address][street].prop'),

            array(self::LEVEL_2, 'address', 'person[address]', 'street', '[street]', 'children[address].children[street].data'),
            array(self::LEVEL_2, 'address', 'person[address]', 'street', '[street]', 'children[address].children[street].data.prop'),
            array(self::LEVEL_1, 'address', 'person[address]', 'street', '[street]', 'children[address].data'),
            array(self::LEVEL_1, 'address', 'person[address]', 'street', '[street]', 'children[address].data.street'),
            array(self::LEVEL_1, 'address', 'person[address]', 'street', '[street]', 'children[address].data.street.prop'),
            array(self::LEVEL_2, 'address', 'person[address]', 'street', '[street]', 'children[address].data[street]'),
            array(self::LEVEL_2, 'address', 'person[address]', 'street', '[street]', 'children[address].data[street].prop'),
            array(self::LEVEL_0, 'address', 'person[address]', 'street', '[street]', 'data.person.address.street'),
            array(self::LEVEL_0, 'address', 'person[address]', 'street', '[street]', 'data.person.address.street.prop'),
            array(self::LEVEL_0, 'address', 'person[address]', 'street', '[street]', 'data.person.address[street]'),
            array(self::LEVEL_0, 'address', 'person[address]', 'street', '[street]', 'data.person.address[street].prop'),
            array(self::LEVEL_1, 'address', 'person[address]', 'street', '[street]', 'data.person[address].street'),
            array(self::LEVEL_1, 'address', 'person[address]', 'street', '[street]', 'data.person[address].street.prop'),
            array(self::LEVEL_2, 'address', 'person[address]', 'street', '[street]', 'data.person[address][street]'),
            array(self::LEVEL_2, 'address', 'person[address]', 'street', '[street]', 'data.person[address][street].prop'),
            array(self::LEVEL_0, 'address', 'person[address]', 'street', '[street]', 'data[person].address.street'),
            array(self::LEVEL_0, 'address', 'person[address]', 'street', '[street]', 'data[person].address.street.prop'),
            array(self::LEVEL_0, 'address', 'person[address]', 'street', '[street]', 'data[person].address[street]'),
            array(self::LEVEL_0, 'address', 'person[address]', 'street', '[street]', 'data[person].address[street].prop'),
            array(self::LEVEL_0, 'address', 'person[address]', 'street', '[street]', 'data[person][address].street'),
            array(self::LEVEL_0, 'address', 'person[address]', 'street', '[street]', 'data[person][address].street.prop'),
            array(self::LEVEL_0, 'address', 'person[address]', 'street', '[street]', 'data[person][address][street]'),
            array(self::LEVEL_0, 'address', 'person[address]', 'street', '[street]', 'data[person][address][street].prop'),

            array(self::LEVEL_2, 'address', '[person].address', 'street', 'street', 'children[address].children[street].data'),
            array(self::LEVEL_2, 'address', '[person].address', 'street', 'street', 'children[address].children[street].data.prop'),
            array(self::LEVEL_1, 'address', '[person].address', 'street', 'street', 'children[address].data'),
            array(self::LEVEL_2, 'address', '[person].address', 'street', 'street', 'children[address].data.street'),
            array(self::LEVEL_2, 'address', '[person].address', 'street', 'street', 'children[address].data.street.prop'),
            array(self::LEVEL_1, 'address', '[person].address', 'street', 'street', 'children[address].data[street]'),
            array(self::LEVEL_1, 'address', '[person].address', 'street', 'street', 'children[address].data[street].prop'),
            array(self::LEVEL_0, 'address', '[person].address', 'street', 'street', 'data.person.address.street'),
            array(self::LEVEL_0, 'address', '[person].address', 'street', 'street', 'data.person.address.street.prop'),
            array(self::LEVEL_0, 'address', '[person].address', 'street', 'street', 'data.person.address[street]'),
            array(self::LEVEL_0, 'address', '[person].address', 'street', 'street', 'data.person.address[street].prop'),
            array(self::LEVEL_0, 'address', '[person].address', 'street', 'street', 'data.person[address].street'),
            array(self::LEVEL_0, 'address', '[person].address', 'street', 'street', 'data.person[address].street.prop'),
            array(self::LEVEL_0, 'address', '[person].address', 'street', 'street', 'data.person[address][street]'),
            array(self::LEVEL_0, 'address', '[person].address', 'street', 'street', 'data.person[address][street].prop'),
            array(self::LEVEL_2, 'address', '[person].address', 'street', 'street', 'data[person].address.street'),
            array(self::LEVEL_2, 'address', '[person].address', 'street', 'street', 'data[person].address.street.prop'),
            array(self::LEVEL_1, 'address', '[person].address', 'street', 'street', 'data[person].address[street]'),
            array(self::LEVEL_1, 'address', '[person].address', 'street', 'street', 'data[person].address[street].prop'),
            array(self::LEVEL_0, 'address', '[person].address', 'street', 'street', 'data[person][address].street'),
            array(self::LEVEL_0, 'address', '[person].address', 'street', 'street', 'data[person][address].street.prop'),
            array(self::LEVEL_0, 'address', '[person].address', 'street', 'street', 'data[person][address][street]'),
            array(self::LEVEL_0, 'address', '[person].address', 'street', 'street', 'data[person][address][street].prop'),

            array(self::LEVEL_2, 'address', '[person].address', 'street', '[street]', 'children[address].children[street].data'),
            array(self::LEVEL_2, 'address', '[person].address', 'street', '[street]', 'children[address].children[street].data.prop'),
            array(self::LEVEL_1, 'address', '[person].address', 'street', '[street]', 'children[address].data'),
            array(self::LEVEL_1, 'address', '[person].address', 'street', '[street]', 'children[address].data.street'),
            array(self::LEVEL_1, 'address', '[person].address', 'street', '[street]', 'children[address].data.street.prop'),
            array(self::LEVEL_2, 'address', '[person].address', 'street', '[street]', 'children[address].data[street]'),
            array(self::LEVEL_2, 'address', '[person].address', 'street', '[street]', 'children[address].data[street].prop'),
            array(self::LEVEL_0, 'address', '[person].address', 'street', '[street]', 'data.person.address.street'),
            array(self::LEVEL_0, 'address', '[person].address', 'street', '[street]', 'data.person.address.street.prop'),
            array(self::LEVEL_0, 'address', '[person].address', 'street', '[street]', 'data.person.address[street]'),
            array(self::LEVEL_0, 'address', '[person].address', 'street', '[street]', 'data.person.address[street].prop'),
            array(self::LEVEL_0, 'address', '[person].address', 'street', '[street]', 'data.person[address].street'),
            array(self::LEVEL_0, 'address', '[person].address', 'street', '[street]', 'data.person[address].street.prop'),
            array(self::LEVEL_0, 'address', '[person].address', 'street', '[street]', 'data.person[address][street]'),
            array(self::LEVEL_0, 'address', '[person].address', 'street', '[street]', 'data.person[address][street].prop'),
            array(self::LEVEL_1, 'address', '[person].address', 'street', '[street]', 'data[person].address.street'),
            array(self::LEVEL_1, 'address', '[person].address', 'street', '[street]', 'data[person].address.street.prop'),
            array(self::LEVEL_2, 'address', '[person].address', 'street', '[street]', 'data[person].address[street]'),
            array(self::LEVEL_2, 'address', '[person].address', 'street', '[street]', 'data[person].address[street].prop'),
            array(self::LEVEL_0, 'address', '[person].address', 'street', '[street]', 'data[person][address].street'),
            array(self::LEVEL_0, 'address', '[person].address', 'street', '[street]', 'data[person][address].street.prop'),
            array(self::LEVEL_0, 'address', '[person].address', 'street', '[street]', 'data[person][address][street]'),
            array(self::LEVEL_0, 'address', '[person].address', 'street', '[street]', 'data[person][address][street].prop'),

            array(self::LEVEL_2, 'address', '[person][address]', 'street', 'street', 'children[address].children[street].data'),
            array(self::LEVEL_2, 'address', '[person][address]', 'street', 'street', 'children[address].children[street].data.prop'),
            array(self::LEVEL_1, 'address', '[person][address]', 'street', 'street', 'children[address]'),
            array(self::LEVEL_1, 'address', '[person][address]', 'street', 'street', 'children[address].data'),
            array(self::LEVEL_2, 'address', '[person][address]', 'street', 'street', 'children[address].data.street'),
            array(self::LEVEL_2, 'address', '[person][address]', 'street', 'street', 'children[address].data.street.prop'),
            array(self::LEVEL_1, 'address', '[person][address]', 'street', 'street', 'children[address].data[street]'),
            array(self::LEVEL_1, 'address', '[person][address]', 'street', 'street', 'children[address].data[street].prop'),
            array(self::LEVEL_0, 'address', '[person][address]', 'street', 'street', 'data.person.address.street'),
            array(self::LEVEL_0, 'address', '[person][address]', 'street', 'street', 'data.person.address.street.prop'),
            array(self::LEVEL_0, 'address', '[person][address]', 'street', 'street', 'data.person.address[street]'),
            array(self::LEVEL_0, 'address', '[person][address]', 'street', 'street', 'data.person.address[street].prop'),
            array(self::LEVEL_0, 'address', '[person][address]', 'street', 'street', 'data.person[address].street'),
            array(self::LEVEL_0, 'address', '[person][address]', 'street', 'street', 'data.person[address].street.prop'),
            array(self::LEVEL_0, 'address', '[person][address]', 'street', 'street', 'data.person[address][street]'),
            array(self::LEVEL_0, 'address', '[person][address]', 'street', 'street', 'data.person[address][street].prop'),
            array(self::LEVEL_0, 'address', '[person][address]', 'street', 'street', 'data[person].address.street'),
            array(self::LEVEL_0, 'address', '[person][address]', 'street', 'street', 'data[person].address.street.prop'),
            array(self::LEVEL_0, 'address', '[person][address]', 'street', 'street', 'data[person].address[street]'),
            array(self::LEVEL_0, 'address', '[person][address]', 'street', 'street', 'data[person].address[street].prop'),
            array(self::LEVEL_2, 'address', '[person][address]', 'street', 'street', 'data[person][address].street'),
            array(self::LEVEL_2, 'address', '[person][address]', 'street', 'street', 'data[person][address].street.prop'),
            array(self::LEVEL_1, 'address', '[person][address]', 'street', 'street', 'data[person][address][street]'),
            array(self::LEVEL_1, 'address', '[person][address]', 'street', 'street', 'data[person][address][street].prop'),

            array(self::LEVEL_2, 'address', '[person][address]', 'street', '[street]', 'children[address].children[street].data'),
            array(self::LEVEL_2, 'address', '[person][address]', 'street', '[street]', 'children[address].children[street].data.prop'),
            array(self::LEVEL_1, 'address', '[person][address]', 'street', '[street]', 'children[address].data'),
            array(self::LEVEL_1, 'address', '[person][address]', 'street', '[street]', 'children[address].data.street'),
            array(self::LEVEL_1, 'address', '[person][address]', 'street', '[street]', 'children[address].data.street.prop'),
            array(self::LEVEL_2, 'address', '[person][address]', 'street', '[street]', 'children[address].data[street]'),
            array(self::LEVEL_2, 'address', '[person][address]', 'street', '[street]', 'children[address].data[street].prop'),
            array(self::LEVEL_0, 'address', '[person][address]', 'street', '[street]', 'data.person.address.street'),
            array(self::LEVEL_0, 'address', '[person][address]', 'street', '[street]', 'data.person.address.street.prop'),
            array(self::LEVEL_0, 'address', '[person][address]', 'street', '[street]', 'data.person.address[street]'),
            array(self::LEVEL_0, 'address', '[person][address]', 'street', '[street]', 'data.person.address[street].prop'),
            array(self::LEVEL_0, 'address', '[person][address]', 'street', '[street]', 'data.person[address].street'),
            array(self::LEVEL_0, 'address', '[person][address]', 'street', '[street]', 'data.person[address].street.prop'),
            array(self::LEVEL_0, 'address', '[person][address]', 'street', '[street]', 'data.person[address][street]'),
            array(self::LEVEL_0, 'address', '[person][address]', 'street', '[street]', 'data.person[address][street].prop'),
            array(self::LEVEL_0, 'address', '[person][address]', 'street', '[street]', 'data[person].address.street'),
            array(self::LEVEL_0, 'address', '[person][address]', 'street', '[street]', 'data[person].address.street.prop'),
            array(self::LEVEL_0, 'address', '[person][address]', 'street', '[street]', 'data[person].address[street]'),
            array(self::LEVEL_0, 'address', '[person][address]', 'street', '[street]', 'data[person].address[street].prop'),
            array(self::LEVEL_1, 'address', '[person][address]', 'street', '[street]', 'data[person][address].street'),
            array(self::LEVEL_1, 'address', '[person][address]', 'street', '[street]', 'data[person][address].street.prop'),
            array(self::LEVEL_2, 'address', '[person][address]', 'street', '[street]', 'data[person][address][street]'),
            array(self::LEVEL_2, 'address', '[person][address]', 'street', '[street]', 'data[person][address][street].prop'),

            array(self::LEVEL_2, 'address', 'address', 'street', 'office.street', 'children[address].children[street].data'),
            array(self::LEVEL_2, 'address', 'address', 'street', 'office.street', 'children[address].children[street].data.prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office.street', 'children[address].data'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office.street', 'children[address].data.office'),
            array(self::LEVEL_2, 'address', 'address', 'street', 'office.street', 'children[address].data.office.street'),
            array(self::LEVEL_2, 'address', 'address', 'street', 'office.street', 'children[address].data.office.street.prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office.street', 'children[address].data.office[street]'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office.street', 'children[address].data.office[street].prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office.street', 'children[address].data[office]'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office.street', 'children[address].data[office].street'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office.street', 'children[address].data[office].street.prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office.street', 'children[address].data[office][street]'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office.street', 'children[address].data[office][street].prop'),
            array(self::LEVEL_2, 'address', 'address', 'street', 'office.street', 'data.address.office.street'),
            array(self::LEVEL_2, 'address', 'address', 'street', 'office.street', 'data.address.office.street.prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office.street', 'data.address.office[street]'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office.street', 'data.address.office[street].prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office.street', 'data.address[office].street'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office.street', 'data.address[office].street.prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office.street', 'data.address[office][street]'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office.street', 'data.address[office][street].prop'),
            array(self::LEVEL_0, 'address', 'address', 'street', 'office.street', 'data[address].office.street'),
            array(self::LEVEL_0, 'address', 'address', 'street', 'office.street', 'data[address].office.street.prop'),
            array(self::LEVEL_0, 'address', 'address', 'street', 'office.street', 'data[address].office[street]'),
            array(self::LEVEL_0, 'address', 'address', 'street', 'office.street', 'data[address].office[street].prop'),
            array(self::LEVEL_0, 'address', 'address', 'street', 'office.street', 'data[address][office].street'),
            array(self::LEVEL_0, 'address', 'address', 'street', 'office.street', 'data[address][office].street.prop'),
            array(self::LEVEL_0, 'address', 'address', 'street', 'office.street', 'data[address][office][street]'),
            array(self::LEVEL_0, 'address', 'address', 'street', 'office.street', 'data[address][office][street].prop'),

            array(self::LEVEL_2, 'address', '[address]', 'street', 'office.street', 'children[address].children[street].data'),
            array(self::LEVEL_2, 'address', '[address]', 'street', 'office.street', 'children[address].children[street].data.prop'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'office.street', 'children[address].data'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'office.street', 'children[address].data.office'),
            array(self::LEVEL_2, 'address', '[address]', 'street', 'office.street', 'children[address].data.office.street'),
            array(self::LEVEL_2, 'address', '[address]', 'street', 'office.street', 'children[address].data.office.street.prop'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'office.street', 'children[address].data.office[street]'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'office.street', 'children[address].data.office[street].prop'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'office.street', 'children[address].data[office]'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'office.street', 'children[address].data[office].street'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'office.street', 'children[address].data[office].street.prop'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'office.street', 'children[address].data[office][street]'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'office.street', 'children[address].data[office][street].prop'),
            array(self::LEVEL_0, 'address', '[address]', 'street', 'office.street', 'data.address.office.street'),
            array(self::LEVEL_0, 'address', '[address]', 'street', 'office.street', 'data.address.office.street.prop'),
            array(self::LEVEL_0, 'address', '[address]', 'street', 'office.street', 'data.address.office[street]'),
            array(self::LEVEL_0, 'address', '[address]', 'street', 'office.street', 'data.address.office[street].prop'),
            array(self::LEVEL_0, 'address', '[address]', 'street', 'office.street', 'data.address[office].street'),
            array(self::LEVEL_0, 'address', '[address]', 'street', 'office.street', 'data.address[office].street.prop'),
            array(self::LEVEL_0, 'address', '[address]', 'street', 'office.street', 'data.address[office][street]'),
            array(self::LEVEL_0, 'address', '[address]', 'street', 'office.street', 'data.address[office][street].prop'),
            array(self::LEVEL_2, 'address', '[address]', 'street', 'office.street', 'data[address].office.street'),
            array(self::LEVEL_2, 'address', '[address]', 'street', 'office.street', 'data[address].office.street.prop'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'office.street', 'data[address].office[street]'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'office.street', 'data[address].office[street].prop'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'office.street', 'data[address][office].street'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'office.street', 'data[address][office].street.prop'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'office.street', 'data[address][office][street]'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'office.street', 'data[address][office][street].prop'),

            array(self::LEVEL_2, 'address', 'address', 'street', 'office[street]', 'children[address].children[street].data'),
            array(self::LEVEL_2, 'address', 'address', 'street', 'office[street]', 'children[address].children[street].data.prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office[street]', 'children[address].data'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office[street]', 'children[address].data.office'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office[street]', 'children[address].data.office.street'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office[street]', 'children[address].data.office.street.prop'),
            array(self::LEVEL_2, 'address', 'address', 'street', 'office[street]', 'children[address].data.office[street]'),
            array(self::LEVEL_2, 'address', 'address', 'street', 'office[street]', 'children[address].data.office[street].prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office[street]', 'children[address].data[office]'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office[street]', 'children[address].data[office].street'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office[street]', 'children[address].data[office].street.prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office[street]', 'children[address].data[office][street]'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office[street]', 'children[address].data[office][street].prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office[street]', 'data.address.office.street'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office[street]', 'data.address.office.street.prop'),
            array(self::LEVEL_2, 'address', 'address', 'street', 'office[street]', 'data.address.office[street]'),
            array(self::LEVEL_2, 'address', 'address', 'street', 'office[street]', 'data.address.office[street].prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office[street]', 'data.address[office].street'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office[street]', 'data.address[office].street.prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office[street]', 'data.address[office][street]'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office[street]', 'data.address[office][street].prop'),
            array(self::LEVEL_0, 'address', 'address', 'street', 'office[street]', 'data[address].office.street'),
            array(self::LEVEL_0, 'address', 'address', 'street', 'office[street]', 'data[address].office.street.prop'),
            array(self::LEVEL_0, 'address', 'address', 'street', 'office[street]', 'data[address].office[street]'),
            array(self::LEVEL_0, 'address', 'address', 'street', 'office[street]', 'data[address].office[street].prop'),
            array(self::LEVEL_0, 'address', 'address', 'street', 'office[street]', 'data[address][office].street'),
            array(self::LEVEL_0, 'address', 'address', 'street', 'office[street]', 'data[address][office].street.prop'),
            array(self::LEVEL_0, 'address', 'address', 'street', 'office[street]', 'data[address][office][street]'),
            array(self::LEVEL_0, 'address', 'address', 'street', 'office[street]', 'data[address][office][street].prop'),

            array(self::LEVEL_2, 'address', '[address]', 'street', 'office[street]', 'children[address].children[street].data'),
            array(self::LEVEL_2, 'address', '[address]', 'street', 'office[street]', 'children[address].children[street].data.prop'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'office[street]', 'children[address].data.office.street'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'office[street]', 'children[address].data.office.street.prop'),
            array(self::LEVEL_2, 'address', '[address]', 'street', 'office[street]', 'children[address].data.office[street]'),
            array(self::LEVEL_2, 'address', '[address]', 'street', 'office[street]', 'children[address].data.office[street].prop'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'office[street]', 'children[address].data[office]'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'office[street]', 'children[address].data[office].street'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'office[street]', 'children[address].data[office].street.prop'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'office[street]', 'children[address].data[office][street]'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'office[street]', 'children[address].data[office][street].prop'),
            array(self::LEVEL_0, 'address', '[address]', 'street', 'office[street]', 'data.address.office.street'),
            array(self::LEVEL_0, 'address', '[address]', 'street', 'office[street]', 'data.address.office.street.prop'),
            array(self::LEVEL_0, 'address', '[address]', 'street', 'office[street]', 'data.address.office[street]'),
            array(self::LEVEL_0, 'address', '[address]', 'street', 'office[street]', 'data.address.office[street].prop'),
            array(self::LEVEL_0, 'address', '[address]', 'street', 'office[street]', 'data.address[office].street'),
            array(self::LEVEL_0, 'address', '[address]', 'street', 'office[street]', 'data.address[office].street.prop'),
            array(self::LEVEL_0, 'address', '[address]', 'street', 'office[street]', 'data.address[office][street]'),
            array(self::LEVEL_0, 'address', '[address]', 'street', 'office[street]', 'data.address[office][street].prop'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'office[street]', 'data[address].office.street'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'office[street]', 'data[address].office.street.prop'),
            array(self::LEVEL_2, 'address', '[address]', 'street', 'office[street]', 'data[address].office[street]'),
            array(self::LEVEL_2, 'address', '[address]', 'street', 'office[street]', 'data[address].office[street].prop'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'office[street]', 'data[address][office].street'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'office[street]', 'data[address][office].street.prop'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'office[street]', 'data[address][office][street]'),
            array(self::LEVEL_1, 'address', '[address]', 'street', 'office[street]', 'data[address][office][street].prop'),

            array(self::LEVEL_2, 'address', 'address', 'street', '[office].street', 'children[address].children[street].data'),
            array(self::LEVEL_2, 'address', 'address', 'street', '[office].street', 'children[address].children[street].data.prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[office].street', 'children[address].data'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[office].street', 'children[address].data.office'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[office].street', 'children[address].data.office.street'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[office].street', 'children[address].data.office.street.prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[office].street', 'children[address].data.office[street]'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[office].street', 'children[address].data.office[street].prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[office].street', 'children[address].data[office]'),
            array(self::LEVEL_2, 'address', 'address', 'street', '[office].street', 'children[address].data[office].street'),
            array(self::LEVEL_2, 'address', 'address', 'street', '[office].street', 'children[address].data[office].street.prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[office].street', 'children[address].data[office][street]'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[office].street', 'children[address].data[office][street].prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[office].street', 'data.address.office.street'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[office].street', 'data.address.office.street.prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[office].street', 'data.address.office[street]'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[office].street', 'data.address.office[street].prop'),
            array(self::LEVEL_2, 'address', 'address', 'street', '[office].street', 'data.address[office].street'),
            array(self::LEVEL_2, 'address', 'address', 'street', '[office].street', 'data.address[office].street.prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[office].street', 'data.address[office][street]'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[office].street', 'data.address[office][street].prop'),
            array(self::LEVEL_0, 'address', 'address', 'street', '[office].street', 'data[address].office.street'),
            array(self::LEVEL_0, 'address', 'address', 'street', '[office].street', 'data[address].office.street.prop'),
            array(self::LEVEL_0, 'address', 'address', 'street', '[office].street', 'data[address].office[street]'),
            array(self::LEVEL_0, 'address', 'address', 'street', '[office].street', 'data[address].office[street].prop'),
            array(self::LEVEL_0, 'address', 'address', 'street', '[office].street', 'data[address][office].street'),
            array(self::LEVEL_0, 'address', 'address', 'street', '[office].street', 'data[address][office].street.prop'),
            array(self::LEVEL_0, 'address', 'address', 'street', '[office].street', 'data[address][office][street]'),
            array(self::LEVEL_0, 'address', 'address', 'street', '[office].street', 'data[address][office][street].prop'),

            array(self::LEVEL_2, 'address', '[address]', 'street', '[office].street', 'children[address].children[street].data'),
            array(self::LEVEL_2, 'address', '[address]', 'street', '[office].street', 'children[address].children[street].data.prop'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[office].street', 'children[address].data'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[office].street', 'children[address].data.office'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[office].street', 'children[address].data.office.street'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[office].street', 'children[address].data.office.street.prop'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[office].street', 'children[address].data.office[street]'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[office].street', 'children[address].data.office[street].prop'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[office].street', 'children[address].data[office]'),
            array(self::LEVEL_2, 'address', '[address]', 'street', '[office].street', 'children[address].data[office].street'),
            array(self::LEVEL_2, 'address', '[address]', 'street', '[office].street', 'children[address].data[office].street.prop'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[office].street', 'children[address].data[office][street]'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[office].street', 'children[address].data[office][street].prop'),
            array(self::LEVEL_0, 'address', '[address]', 'street', '[office].street', 'data.address.office.street'),
            array(self::LEVEL_0, 'address', '[address]', 'street', '[office].street', 'data.address.office.street.prop'),
            array(self::LEVEL_0, 'address', '[address]', 'street', '[office].street', 'data.address.office[street]'),
            array(self::LEVEL_0, 'address', '[address]', 'street', '[office].street', 'data.address.office[street].prop'),
            array(self::LEVEL_0, 'address', '[address]', 'street', '[office].street', 'data.address[office].street'),
            array(self::LEVEL_0, 'address', '[address]', 'street', '[office].street', 'data.address[office].street.prop'),
            array(self::LEVEL_0, 'address', '[address]', 'street', '[office].street', 'data.address[office][street]'),
            array(self::LEVEL_0, 'address', '[address]', 'street', '[office].street', 'data.address[office][street].prop'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[office].street', 'data[address].office.street'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[office].street', 'data[address].office.street.prop'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[office].street', 'data[address].office[street]'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[office].street', 'data[address].office[street].prop'),
            array(self::LEVEL_2, 'address', '[address]', 'street', '[office].street', 'data[address][office].street'),
            array(self::LEVEL_2, 'address', '[address]', 'street', '[office].street', 'data[address][office].street.prop'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[office].street', 'data[address][office][street]'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[office].street', 'data[address][office][street].prop'),

            array(self::LEVEL_2, 'address', 'address', 'street', '[office][street]', 'children[address].children[street].data'),
            array(self::LEVEL_2, 'address', 'address', 'street', '[office][street]', 'children[address].children[street].data.prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[office][street]', 'children[address].data'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[office][street]', 'children[address].data.office'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[office][street]', 'children[address].data.office.street'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[office][street]', 'children[address].data.office.street.prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[office][street]', 'children[address].data.office[street]'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[office][street]', 'children[address].data.office[street].prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[office][street]', 'children[address].data[office]'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[office][street]', 'children[address].data[office].street'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[office][street]', 'children[address].data[office].street.prop'),
            array(self::LEVEL_2, 'address', 'address', 'street', '[office][street]', 'children[address].data[office][street]'),
            array(self::LEVEL_2, 'address', 'address', 'street', '[office][street]', 'children[address].data[office][street].prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[office][street]', 'data.address.office.street'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[office][street]', 'data.address.office.street.prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[office][street]', 'data.address.office[street]'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[office][street]', 'data.address.office[street].prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[office][street]', 'data.address[office].street'),
            array(self::LEVEL_1, 'address', 'address', 'street', '[office][street]', 'data.address[office].street.prop'),
            array(self::LEVEL_2, 'address', 'address', 'street', '[office][street]', 'data.address[office][street]'),
            array(self::LEVEL_2, 'address', 'address', 'street', '[office][street]', 'data.address[office][street].prop'),
            array(self::LEVEL_0, 'address', 'address', 'street', '[office][street]', 'data[address].office.street'),
            array(self::LEVEL_0, 'address', 'address', 'street', '[office][street]', 'data[address].office.street.prop'),
            array(self::LEVEL_0, 'address', 'address', 'street', '[office][street]', 'data[address].office[street]'),
            array(self::LEVEL_0, 'address', 'address', 'street', '[office][street]', 'data[address].office[street].prop'),
            array(self::LEVEL_0, 'address', 'address', 'street', '[office][street]', 'data[address][office].street'),
            array(self::LEVEL_0, 'address', 'address', 'street', '[office][street]', 'data[address][office].street.prop'),
            array(self::LEVEL_0, 'address', 'address', 'street', '[office][street]', 'data[address][office][street]'),
            array(self::LEVEL_0, 'address', 'address', 'street', '[office][street]', 'data[address][office][street].prop'),

            array(self::LEVEL_2, 'address', '[address]', 'street', '[office][street]', 'children[address].children[street].data'),
            array(self::LEVEL_2, 'address', '[address]', 'street', '[office][street]', 'children[address].children[street].data.prop'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[office][street]', 'children[address].data'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[office][street]', 'children[address].data.office'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[office][street]', 'children[address].data.office.street'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[office][street]', 'children[address].data.office.street.prop'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[office][street]', 'children[address].data.office[street]'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[office][street]', 'children[address].data.office[street].prop'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[office][street]', 'children[address].data[office]'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[office][street]', 'children[address].data[office].street'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[office][street]', 'children[address].data[office].street.prop'),
            array(self::LEVEL_2, 'address', '[address]', 'street', '[office][street]', 'children[address].data[office][street]'),
            array(self::LEVEL_2, 'address', '[address]', 'street', '[office][street]', 'children[address].data[office][street].prop'),
            array(self::LEVEL_0, 'address', '[address]', 'street', '[office][street]', 'data.address.office.street'),
            array(self::LEVEL_0, 'address', '[address]', 'street', '[office][street]', 'data.address.office.street.prop'),
            array(self::LEVEL_0, 'address', '[address]', 'street', '[office][street]', 'data.address.office[street]'),
            array(self::LEVEL_0, 'address', '[address]', 'street', '[office][street]', 'data.address.office[street].prop'),
            array(self::LEVEL_0, 'address', '[address]', 'street', '[office][street]', 'data.address[office].street'),
            array(self::LEVEL_0, 'address', '[address]', 'street', '[office][street]', 'data.address[office].street.prop'),
            array(self::LEVEL_0, 'address', '[address]', 'street', '[office][street]', 'data.address[office][street]'),
            array(self::LEVEL_0, 'address', '[address]', 'street', '[office][street]', 'data.address[office][street].prop'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[office][street]', 'data[address].office.street'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[office][street]', 'data[address].office.street.prop'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[office][street]', 'data[address].office[street]'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[office][street]', 'data[address].office[street].prop'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[office][street]', 'data[address][office].street'),
            array(self::LEVEL_1, 'address', '[address]', 'street', '[office][street]', 'data[address][office].street.prop'),
            array(self::LEVEL_2, 'address', '[address]', 'street', '[office][street]', 'data[address][office][street]'),
            array(self::LEVEL_2, 'address', '[address]', 'street', '[office][street]', 'data[address][office][street].prop'),

            // Edge cases which must not occur
            array(self::LEVEL_2, 'address', 'address', 'street', 'street', 'children[address][street]'),
            array(self::LEVEL_2, 'address', 'address', 'street', 'street', 'children[address][street].prop'),
            array(self::LEVEL_2, 'address', 'address', 'street', '[street]', 'children[address][street]'),
            array(self::LEVEL_2, 'address', 'address', 'street', '[street]', 'children[address][street].prop'),
            array(self::LEVEL_2, 'address', '[address]', 'street', 'street', 'children[address][street]'),
            array(self::LEVEL_2, 'address', '[address]', 'street', 'street', 'children[address][street].prop'),
            array(self::LEVEL_2, 'address', '[address]', 'street', '[street]', 'children[address][street]'),
            array(self::LEVEL_2, 'address', '[address]', 'street', '[street]', 'children[address][street].prop'),

            array(self::LEVEL_0, 'address', 'person.address', 'street', 'street', 'children[person].children[address].children[street]'),
            array(self::LEVEL_0, 'address', 'person.address', 'street', 'street', 'children[person].children[address].data.street'),
            array(self::LEVEL_0, 'address', 'person.address', 'street', 'street', 'children[person].data.address.street'),
            array(self::LEVEL_0, 'address', 'person.address', 'street', 'street', 'data.address.street'),

            array(self::LEVEL_1, 'address', 'address', 'street', 'office.street', 'children[address].children[office].children[street]'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office.street', 'children[address].children[office].data.street'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office.street', 'children[address].data.street'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'office.street', 'data.address.street'),
        );
    }

    /**
     * @dataProvider provideDefaultTests
     */
    public function testDefaultErrorMapping($target, $childName, $childPath, $grandChildName, $grandChildPath, $violationPath)
    {
        $violation = $this->getConstraintViolation($violationPath);
        $parent = $this->getForm('parent');
        $child = $this->getForm($childName, $childPath);
        $grandChild = $this->getForm($grandChildName, $grandChildPath);

        $parent->add($child);
        $child->add($grandChild);

        $parent->submit(array());

        $this->mapper->mapViolation($violation, $parent);

        if (self::LEVEL_0 === $target) {
            $this->assertEquals(array($this->getFormError($violation, $parent)), iterator_to_array($parent->getErrors()), $parent->getName().' should have an error, but has none');
            $this->assertCount(0, $child->getErrors(), $childName.' should not have an error, but has one');
            $this->assertCount(0, $grandChild->getErrors(), $grandChildName.' should not have an error, but has one');
        } elseif (self::LEVEL_1 === $target) {
            $this->assertCount(0, $parent->getErrors(), $parent->getName().' should not have an error, but has one');
            $this->assertEquals(array($this->getFormError($violation, $child)), iterator_to_array($child->getErrors()), $childName.' should have an error, but has none');
            $this->assertCount(0, $grandChild->getErrors(), $grandChildName.' should not have an error, but has one');
        } else {
            $this->assertCount(0, $parent->getErrors(), $parent->getName().' should not have an error, but has one');
            $this->assertCount(0, $child->getErrors(), $childName.' should not have an error, but has one');
            $this->assertEquals(array($this->getFormError($violation, $grandChild)), iterator_to_array($grandChild->getErrors()), $grandChildName.' should have an error, but has none');
        }
    }

    public function provideCustomDataErrorTests()
    {
        return array(
            // mapping target, error mapping, child name, its property path, grand child name, its property path, violation path
            array(self::LEVEL_1, 'foo', 'address', 'address', 'address', 'street', 'street', 'data.foo'),
            array(self::LEVEL_1, 'foo', 'address', 'address', 'address', 'street', 'street', 'data.foo.prop'),
            array(self::LEVEL_0, 'foo', 'address', 'address', 'address', 'street', 'street', 'data[foo]'),
            array(self::LEVEL_0, 'foo', 'address', 'address', 'address', 'street', 'street', 'data[foo].prop'),

            array(self::LEVEL_1, 'foo', 'address', 'address', 'address', 'street', 'street', 'data.address'),
            array(self::LEVEL_1, 'foo', 'address', 'address', 'address', 'street', 'street', 'data.address.prop'),
            array(self::LEVEL_0, 'foo', 'address', 'address', 'address', 'street', 'street', 'data[address]'),
            array(self::LEVEL_0, 'foo', 'address', 'address', 'address', 'street', 'street', 'data[address].prop'),

            array(self::LEVEL_1, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data.foo'),
            array(self::LEVEL_1, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data.foo.prop'),
            array(self::LEVEL_0, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data[foo]'),
            array(self::LEVEL_0, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data[foo].prop'),

            array(self::LEVEL_0, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data.address'),
            array(self::LEVEL_0, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data.address.prop'),
            array(self::LEVEL_1, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data[address]'),
            array(self::LEVEL_1, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data[address].prop'),

            array(self::LEVEL_0, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data.foo'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data.foo.prop'),
            array(self::LEVEL_1, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data[foo]'),
            array(self::LEVEL_1, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data[foo].prop'),

            array(self::LEVEL_1, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data.address'),
            array(self::LEVEL_1, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data.address.prop'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data[address]'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data[address].prop'),

            array(self::LEVEL_0, '[foo]', 'address', 'address', '[address]', 'street', 'street', 'data.foo'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', '[address]', 'street', 'street', 'data.foo.prop'),
            array(self::LEVEL_1, '[foo]', 'address', 'address', '[address]', 'street', 'street', 'data[foo]'),
            array(self::LEVEL_1, '[foo]', 'address', 'address', '[address]', 'street', 'street', 'data[foo].prop'),

            array(self::LEVEL_0, '[foo]', 'address', 'address', '[address]', 'street', 'street', 'data.address'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', '[address]', 'street', 'street', 'data.address.prop'),
            array(self::LEVEL_1, '[foo]', 'address', 'address', '[address]', 'street', 'street', 'data[address]'),
            array(self::LEVEL_1, '[foo]', 'address', 'address', '[address]', 'street', 'street', 'data[address].prop'),

            array(self::LEVEL_2, 'foo', 'address', 'address', 'address', 'street', 'street', 'data.foo.street'),
            array(self::LEVEL_2, 'foo', 'address', 'address', 'address', 'street', 'street', 'data.foo.street.prop'),
            array(self::LEVEL_1, 'foo', 'address', 'address', 'address', 'street', 'street', 'data.foo[street]'),
            array(self::LEVEL_1, 'foo', 'address', 'address', 'address', 'street', 'street', 'data.foo[street].prop'),
            array(self::LEVEL_0, 'foo', 'address', 'address', 'address', 'street', 'street', 'data[foo].street'),
            array(self::LEVEL_0, 'foo', 'address', 'address', 'address', 'street', 'street', 'data[foo].street.prop'),
            array(self::LEVEL_0, 'foo', 'address', 'address', 'address', 'street', 'street', 'data[foo][street]'),
            array(self::LEVEL_0, 'foo', 'address', 'address', 'address', 'street', 'street', 'data[foo][street].prop'),

            array(self::LEVEL_2, 'foo', 'address', 'address', 'address', 'street', 'street', 'data.address.street'),
            array(self::LEVEL_2, 'foo', 'address', 'address', 'address', 'street', 'street', 'data.address.street.prop'),
            array(self::LEVEL_1, 'foo', 'address', 'address', 'address', 'street', 'street', 'data.address[street]'),
            array(self::LEVEL_1, 'foo', 'address', 'address', 'address', 'street', 'street', 'data.address[street].prop'),
            array(self::LEVEL_0, 'foo', 'address', 'address', 'address', 'street', 'street', 'data[address].street'),
            array(self::LEVEL_0, 'foo', 'address', 'address', 'address', 'street', 'street', 'data[address].street.prop'),
            array(self::LEVEL_0, 'foo', 'address', 'address', 'address', 'street', 'street', 'data[address][street]'),
            array(self::LEVEL_0, 'foo', 'address', 'address', 'address', 'street', 'street', 'data[address][street].prop'),

            array(self::LEVEL_1, 'foo', 'address', 'address', 'address', 'street', '[street]', 'data.foo.street'),
            array(self::LEVEL_1, 'foo', 'address', 'address', 'address', 'street', '[street]', 'data.foo.street.prop'),
            array(self::LEVEL_2, 'foo', 'address', 'address', 'address', 'street', '[street]', 'data.foo[street]'),
            array(self::LEVEL_2, 'foo', 'address', 'address', 'address', 'street', '[street]', 'data.foo[street].prop'),
            array(self::LEVEL_0, 'foo', 'address', 'address', 'address', 'street', '[street]', 'data[foo].street'),
            array(self::LEVEL_0, 'foo', 'address', 'address', 'address', 'street', '[street]', 'data[foo].street.prop'),
            array(self::LEVEL_0, 'foo', 'address', 'address', 'address', 'street', '[street]', 'data[foo][street]'),
            array(self::LEVEL_0, 'foo', 'address', 'address', 'address', 'street', '[street]', 'data[foo][street].prop'),

            array(self::LEVEL_1, 'foo', 'address', 'address', 'address', 'street', '[street]', 'data.address.street'),
            array(self::LEVEL_1, 'foo', 'address', 'address', 'address', 'street', '[street]', 'data.address.street.prop'),
            array(self::LEVEL_2, 'foo', 'address', 'address', 'address', 'street', '[street]', 'data.address[street]'),
            array(self::LEVEL_2, 'foo', 'address', 'address', 'address', 'street', '[street]', 'data.address[street].prop'),
            array(self::LEVEL_0, 'foo', 'address', 'address', 'address', 'street', '[street]', 'data[address].street'),
            array(self::LEVEL_0, 'foo', 'address', 'address', 'address', 'street', '[street]', 'data[address].street.prop'),
            array(self::LEVEL_0, 'foo', 'address', 'address', 'address', 'street', '[street]', 'data[address][street]'),
            array(self::LEVEL_0, 'foo', 'address', 'address', 'address', 'street', '[street]', 'data[address][street].prop'),

            array(self::LEVEL_2, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data.foo.street'),
            array(self::LEVEL_2, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data.foo.street.prop'),
            array(self::LEVEL_1, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data.foo[street]'),
            array(self::LEVEL_1, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data.foo[street].prop'),
            array(self::LEVEL_0, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data[foo].street'),
            array(self::LEVEL_0, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data[foo].street.prop'),
            array(self::LEVEL_0, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data[foo][street]'),
            array(self::LEVEL_0, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data[foo][street].prop'),

            array(self::LEVEL_0, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data.address.street'),
            array(self::LEVEL_0, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data.address.street.prop'),
            array(self::LEVEL_0, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data.address[street]'),
            array(self::LEVEL_0, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data.address[street].prop'),
            array(self::LEVEL_2, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data[address].street'),
            array(self::LEVEL_2, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data[address].street.prop'),
            array(self::LEVEL_1, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data[address][street]'),
            array(self::LEVEL_1, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data[address][street].prop'),

            array(self::LEVEL_1, 'foo', 'address', 'address', '[address]', 'street', '[street]', 'data.foo.street'),
            array(self::LEVEL_1, 'foo', 'address', 'address', '[address]', 'street', '[street]', 'data.foo.street.prop'),
            array(self::LEVEL_2, 'foo', 'address', 'address', '[address]', 'street', '[street]', 'data.foo[street]'),
            array(self::LEVEL_2, 'foo', 'address', 'address', '[address]', 'street', '[street]', 'data.foo[street].prop'),
            array(self::LEVEL_0, 'foo', 'address', 'address', '[address]', 'street', '[street]', 'data[foo].street'),
            array(self::LEVEL_0, 'foo', 'address', 'address', '[address]', 'street', '[street]', 'data[foo].street.prop'),
            array(self::LEVEL_0, 'foo', 'address', 'address', '[address]', 'street', '[street]', 'data[foo][street]'),
            array(self::LEVEL_0, 'foo', 'address', 'address', '[address]', 'street', '[street]', 'data[foo][street].prop'),

            array(self::LEVEL_0, 'foo', 'address', 'address', '[address]', 'street', '[street]', 'data.address.street'),
            array(self::LEVEL_0, 'foo', 'address', 'address', '[address]', 'street', '[street]', 'data.address.street.prop'),
            array(self::LEVEL_0, 'foo', 'address', 'address', '[address]', 'street', '[street]', 'data.address[street]'),
            array(self::LEVEL_0, 'foo', 'address', 'address', '[address]', 'street', '[street]', 'data.address[street].prop'),
            array(self::LEVEL_1, 'foo', 'address', 'address', '[address]', 'street', '[street]', 'data[address].street'),
            array(self::LEVEL_1, 'foo', 'address', 'address', '[address]', 'street', '[street]', 'data[address].street.prop'),
            array(self::LEVEL_2, 'foo', 'address', 'address', '[address]', 'street', '[street]', 'data[address][street]'),
            array(self::LEVEL_2, 'foo', 'address', 'address', '[address]', 'street', '[street]', 'data[address][street].prop'),

            array(self::LEVEL_0, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data.foo.street'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data.foo.street.prop'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data.foo[street]'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data.foo[street].prop'),
            array(self::LEVEL_2, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data[foo].street'),
            array(self::LEVEL_2, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data[foo].street.prop'),
            array(self::LEVEL_1, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data[foo][street]'),
            array(self::LEVEL_1, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data[foo][street].prop'),

            array(self::LEVEL_2, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data.address.street'),
            array(self::LEVEL_2, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data.address.street.prop'),
            array(self::LEVEL_1, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data.address[street]'),
            array(self::LEVEL_1, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data.address[street].prop'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data[address].street'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data[address].street.prop'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data[address][street]'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data[address][street].prop'),

            array(self::LEVEL_0, '[foo]', 'address', 'address', 'address', 'street', '[street]', 'data.foo.street'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', 'address', 'street', '[street]', 'data.foo.street.prop'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', 'address', 'street', '[street]', 'data.foo[street]'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', 'address', 'street', '[street]', 'data.foo[street].prop'),
            array(self::LEVEL_1, '[foo]', 'address', 'address', 'address', 'street', '[street]', 'data[foo].street'),
            array(self::LEVEL_1, '[foo]', 'address', 'address', 'address', 'street', '[street]', 'data[foo].street.prop'),
            array(self::LEVEL_2, '[foo]', 'address', 'address', 'address', 'street', '[street]', 'data[foo][street]'),
            array(self::LEVEL_2, '[foo]', 'address', 'address', 'address', 'street', '[street]', 'data[foo][street].prop'),

            array(self::LEVEL_1, '[foo]', 'address', 'address', 'address', 'street', '[street]', 'data.address.street'),
            array(self::LEVEL_1, '[foo]', 'address', 'address', 'address', 'street', '[street]', 'data.address.street.prop'),
            array(self::LEVEL_2, '[foo]', 'address', 'address', 'address', 'street', '[street]', 'data.address[street]'),
            array(self::LEVEL_2, '[foo]', 'address', 'address', 'address', 'street', '[street]', 'data.address[street].prop'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', 'address', 'street', '[street]', 'data[address].street'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', 'address', 'street', '[street]', 'data[address].street.prop'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', 'address', 'street', '[street]', 'data[address][street]'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', 'address', 'street', '[street]', 'data[address][street].prop'),

            array(self::LEVEL_0, '[foo]', 'address', 'address', '[address]', 'street', 'street', 'data.foo.street'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', '[address]', 'street', 'street', 'data.foo.street.prop'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', '[address]', 'street', 'street', 'data.foo[street]'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', '[address]', 'street', 'street', 'data.foo[street].prop'),
            array(self::LEVEL_2, '[foo]', 'address', 'address', '[address]', 'street', 'street', 'data[foo].street'),
            array(self::LEVEL_2, '[foo]', 'address', 'address', '[address]', 'street', 'street', 'data[foo].street.prop'),
            array(self::LEVEL_1, '[foo]', 'address', 'address', '[address]', 'street', 'street', 'data[foo][street]'),
            array(self::LEVEL_1, '[foo]', 'address', 'address', '[address]', 'street', 'street', 'data[foo][street].prop'),

            array(self::LEVEL_0, '[foo]', 'address', 'address', '[address]', 'street', 'street', 'data.address.street'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', '[address]', 'street', 'street', 'data.address.street.prop'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', '[address]', 'street', 'street', 'data.address[street]'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', '[address]', 'street', 'street', 'data.address[street].prop'),
            array(self::LEVEL_2, '[foo]', 'address', 'address', '[address]', 'street', 'street', 'data[address].street'),
            array(self::LEVEL_2, '[foo]', 'address', 'address', '[address]', 'street', 'street', 'data[address].street.prop'),
            array(self::LEVEL_1, '[foo]', 'address', 'address', '[address]', 'street', 'street', 'data[address][street]'),
            array(self::LEVEL_1, '[foo]', 'address', 'address', '[address]', 'street', 'street', 'data[address][street].prop'),

            array(self::LEVEL_0, '[foo]', 'address', 'address', '[address]', 'street', '[street]', 'data.foo.street'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', '[address]', 'street', '[street]', 'data.foo.street.prop'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', '[address]', 'street', '[street]', 'data.foo[street]'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', '[address]', 'street', '[street]', 'data.foo[street].prop'),
            array(self::LEVEL_1, '[foo]', 'address', 'address', '[address]', 'street', '[street]', 'data[foo].street'),
            array(self::LEVEL_1, '[foo]', 'address', 'address', '[address]', 'street', '[street]', 'data[foo].street.prop'),
            array(self::LEVEL_2, '[foo]', 'address', 'address', '[address]', 'street', '[street]', 'data[foo][street]'),
            array(self::LEVEL_2, '[foo]', 'address', 'address', '[address]', 'street', '[street]', 'data[foo][street].prop'),

            array(self::LEVEL_0, '[foo]', 'address', 'address', '[address]', 'street', '[street]', 'data.address.street'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', '[address]', 'street', '[street]', 'data.address.street.prop'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', '[address]', 'street', '[street]', 'data.address[street]'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', '[address]', 'street', '[street]', 'data.address[street].prop'),
            array(self::LEVEL_1, '[foo]', 'address', 'address', '[address]', 'street', '[street]', 'data[address].street'),
            array(self::LEVEL_1, '[foo]', 'address', 'address', '[address]', 'street', '[street]', 'data[address].street.prop'),
            array(self::LEVEL_2, '[foo]', 'address', 'address', '[address]', 'street', '[street]', 'data[address][street]'),
            array(self::LEVEL_2, '[foo]', 'address', 'address', '[address]', 'street', '[street]', 'data[address][street].prop'),

            array(self::LEVEL_1, 'foo.bar', 'address', 'address', 'address', 'street', 'street', 'data.foo.bar'),
            array(self::LEVEL_1, 'foo.bar', 'address', 'address', 'address', 'street', 'street', 'data.foo.bar.prop'),
            array(self::LEVEL_0, 'foo.bar', 'address', 'address', 'address', 'street', 'street', 'data.foo[bar]'),
            array(self::LEVEL_0, 'foo.bar', 'address', 'address', 'address', 'street', 'street', 'data.foo[bar].prop'),
            array(self::LEVEL_0, 'foo.bar', 'address', 'address', 'address', 'street', 'street', 'data[foo].bar'),
            array(self::LEVEL_0, 'foo.bar', 'address', 'address', 'address', 'street', 'street', 'data[foo].bar.prop'),
            array(self::LEVEL_0, 'foo.bar', 'address', 'address', 'address', 'street', 'street', 'data[foo][bar]'),
            array(self::LEVEL_0, 'foo.bar', 'address', 'address', 'address', 'street', 'street', 'data[foo][bar].prop'),

            array(self::LEVEL_0, 'foo[bar]', 'address', 'address', 'address', 'street', 'street', 'data.foo.bar'),
            array(self::LEVEL_0, 'foo[bar]', 'address', 'address', 'address', 'street', 'street', 'data.foo.bar.prop'),
            array(self::LEVEL_1, 'foo[bar]', 'address', 'address', 'address', 'street', 'street', 'data.foo[bar]'),
            array(self::LEVEL_1, 'foo[bar]', 'address', 'address', 'address', 'street', 'street', 'data.foo[bar].prop'),
            array(self::LEVEL_0, 'foo[bar]', 'address', 'address', 'address', 'street', 'street', 'data[foo].bar'),
            array(self::LEVEL_0, 'foo[bar]', 'address', 'address', 'address', 'street', 'street', 'data[foo].bar.prop'),
            array(self::LEVEL_0, 'foo[bar]', 'address', 'address', 'address', 'street', 'street', 'data[foo][bar]'),
            array(self::LEVEL_0, 'foo[bar]', 'address', 'address', 'address', 'street', 'street', 'data[foo][bar].prop'),

            array(self::LEVEL_0, '[foo].bar', 'address', 'address', 'address', 'street', 'street', 'data.foo.bar'),
            array(self::LEVEL_0, '[foo].bar', 'address', 'address', 'address', 'street', 'street', 'data.foo.bar.prop'),
            array(self::LEVEL_0, '[foo].bar', 'address', 'address', 'address', 'street', 'street', 'data.foo[bar]'),
            array(self::LEVEL_0, '[foo].bar', 'address', 'address', 'address', 'street', 'street', 'data.foo[bar].prop'),
            array(self::LEVEL_1, '[foo].bar', 'address', 'address', 'address', 'street', 'street', 'data[foo].bar'),
            array(self::LEVEL_1, '[foo].bar', 'address', 'address', 'address', 'street', 'street', 'data[foo].bar.prop'),
            array(self::LEVEL_0, '[foo].bar', 'address', 'address', 'address', 'street', 'street', 'data[foo][bar]'),
            array(self::LEVEL_0, '[foo].bar', 'address', 'address', 'address', 'street', 'street', 'data[foo][bar].prop'),

            array(self::LEVEL_0, '[foo][bar]', 'address', 'address', 'address', 'street', 'street', 'data.foo.bar'),
            array(self::LEVEL_0, '[foo][bar]', 'address', 'address', 'address', 'street', 'street', 'data.foo.bar.prop'),
            array(self::LEVEL_0, '[foo][bar]', 'address', 'address', 'address', 'street', 'street', 'data.foo[bar]'),
            array(self::LEVEL_0, '[foo][bar]', 'address', 'address', 'address', 'street', 'street', 'data.foo[bar].prop'),
            array(self::LEVEL_0, '[foo][bar]', 'address', 'address', 'address', 'street', 'street', 'data[foo].bar'),
            array(self::LEVEL_0, '[foo][bar]', 'address', 'address', 'address', 'street', 'street', 'data[foo].bar.prop'),
            array(self::LEVEL_1, '[foo][bar]', 'address', 'address', 'address', 'street', 'street', 'data[foo][bar]'),
            array(self::LEVEL_1, '[foo][bar]', 'address', 'address', 'address', 'street', 'street', 'data[foo][bar].prop'),

            array(self::LEVEL_2, 'foo.bar', 'address', 'address', 'address', 'street', 'street', 'data.foo.bar.street'),
            array(self::LEVEL_2, 'foo.bar', 'address', 'address', 'address', 'street', 'street', 'data.foo.bar.street.prop'),
            array(self::LEVEL_1, 'foo.bar', 'address', 'address', 'address', 'street', 'street', 'data.foo.bar[street]'),
            array(self::LEVEL_1, 'foo.bar', 'address', 'address', 'address', 'street', 'street', 'data.foo.bar[street].prop'),
            array(self::LEVEL_0, 'foo.bar', 'address', 'address', 'address', 'street', 'street', 'data.foo[bar].street'),
            array(self::LEVEL_0, 'foo.bar', 'address', 'address', 'address', 'street', 'street', 'data.foo[bar].street.prop'),
            array(self::LEVEL_0, 'foo.bar', 'address', 'address', 'address', 'street', 'street', 'data.foo[bar][street]'),
            array(self::LEVEL_0, 'foo.bar', 'address', 'address', 'address', 'street', 'street', 'data.foo[bar][street].prop'),
            array(self::LEVEL_0, 'foo.bar', 'address', 'address', 'address', 'street', 'street', 'data[foo].bar.street'),
            array(self::LEVEL_0, 'foo.bar', 'address', 'address', 'address', 'street', 'street', 'data[foo].bar.street.prop'),
            array(self::LEVEL_0, 'foo.bar', 'address', 'address', 'address', 'street', 'street', 'data[foo].bar[street]'),
            array(self::LEVEL_0, 'foo.bar', 'address', 'address', 'address', 'street', 'street', 'data[foo].bar[street].prop'),
            array(self::LEVEL_0, 'foo.bar', 'address', 'address', 'address', 'street', 'street', 'data[foo][bar].street'),
            array(self::LEVEL_0, 'foo.bar', 'address', 'address', 'address', 'street', 'street', 'data[foo][bar].street.prop'),
            array(self::LEVEL_0, 'foo.bar', 'address', 'address', 'address', 'street', 'street', 'data[foo][bar][street]'),
            array(self::LEVEL_0, 'foo.bar', 'address', 'address', 'address', 'street', 'street', 'data[foo][bar][street].prop'),

            array(self::LEVEL_1, 'foo.bar', 'address', 'address', 'address', 'street', '[street]', 'data.foo.bar.street'),
            array(self::LEVEL_1, 'foo.bar', 'address', 'address', 'address', 'street', '[street]', 'data.foo.bar.street.prop'),
            array(self::LEVEL_2, 'foo.bar', 'address', 'address', 'address', 'street', '[street]', 'data.foo.bar[street]'),
            array(self::LEVEL_2, 'foo.bar', 'address', 'address', 'address', 'street', '[street]', 'data.foo.bar[street].prop'),
            array(self::LEVEL_0, 'foo.bar', 'address', 'address', 'address', 'street', '[street]', 'data.foo[bar].street'),
            array(self::LEVEL_0, 'foo.bar', 'address', 'address', 'address', 'street', '[street]', 'data.foo[bar].street.prop'),
            array(self::LEVEL_0, 'foo.bar', 'address', 'address', 'address', 'street', '[street]', 'data.foo[bar][street]'),
            array(self::LEVEL_0, 'foo.bar', 'address', 'address', 'address', 'street', '[street]', 'data.foo[bar][street].prop'),
            array(self::LEVEL_0, 'foo.bar', 'address', 'address', 'address', 'street', '[street]', 'data[foo].bar.street'),
            array(self::LEVEL_0, 'foo.bar', 'address', 'address', 'address', 'street', '[street]', 'data[foo].bar.street.prop'),
            array(self::LEVEL_0, 'foo.bar', 'address', 'address', 'address', 'street', '[street]', 'data[foo].bar[street]'),
            array(self::LEVEL_0, 'foo.bar', 'address', 'address', 'address', 'street', '[street]', 'data[foo].bar[street].prop'),
            array(self::LEVEL_0, 'foo.bar', 'address', 'address', 'address', 'street', '[street]', 'data[foo][bar].street'),
            array(self::LEVEL_0, 'foo.bar', 'address', 'address', 'address', 'street', '[street]', 'data[foo][bar].street.prop'),
            array(self::LEVEL_0, 'foo.bar', 'address', 'address', 'address', 'street', '[street]', 'data[foo][bar][street]'),
            array(self::LEVEL_0, 'foo.bar', 'address', 'address', 'address', 'street', '[street]', 'data[foo][bar][street].prop'),

            array(self::LEVEL_0, 'foo[bar]', 'address', 'address', 'address', 'street', 'street', 'data.foo.bar.street'),
            array(self::LEVEL_0, 'foo[bar]', 'address', 'address', 'address', 'street', 'street', 'data.foo.bar.street.prop'),
            array(self::LEVEL_0, 'foo[bar]', 'address', 'address', 'address', 'street', 'street', 'data.foo.bar[street]'),
            array(self::LEVEL_0, 'foo[bar]', 'address', 'address', 'address', 'street', 'street', 'data.foo.bar[street].prop'),
            array(self::LEVEL_2, 'foo[bar]', 'address', 'address', 'address', 'street', 'street', 'data.foo[bar].street'),
            array(self::LEVEL_2, 'foo[bar]', 'address', 'address', 'address', 'street', 'street', 'data.foo[bar].street.prop'),
            array(self::LEVEL_1, 'foo[bar]', 'address', 'address', 'address', 'street', 'street', 'data.foo[bar][street]'),
            array(self::LEVEL_1, 'foo[bar]', 'address', 'address', 'address', 'street', 'street', 'data.foo[bar][street].prop'),
            array(self::LEVEL_0, 'foo[bar]', 'address', 'address', 'address', 'street', 'street', 'data[foo].bar.street'),
            array(self::LEVEL_0, 'foo[bar]', 'address', 'address', 'address', 'street', 'street', 'data[foo].bar.street.prop'),
            array(self::LEVEL_0, 'foo[bar]', 'address', 'address', 'address', 'street', 'street', 'data[foo].bar[street]'),
            array(self::LEVEL_0, 'foo[bar]', 'address', 'address', 'address', 'street', 'street', 'data[foo].bar[street].prop'),
            array(self::LEVEL_0, 'foo[bar]', 'address', 'address', 'address', 'street', 'street', 'data[foo][bar].street'),
            array(self::LEVEL_0, 'foo[bar]', 'address', 'address', 'address', 'street', 'street', 'data[foo][bar].street.prop'),
            array(self::LEVEL_0, 'foo[bar]', 'address', 'address', 'address', 'street', 'street', 'data[foo][bar][street]'),
            array(self::LEVEL_0, 'foo[bar]', 'address', 'address', 'address', 'street', 'street', 'data[foo][bar][street].prop'),

            array(self::LEVEL_0, 'foo[bar]', 'address', 'address', 'address', 'street', '[street]', 'data.foo.bar.street'),
            array(self::LEVEL_0, 'foo[bar]', 'address', 'address', 'address', 'street', '[street]', 'data.foo.bar.street.prop'),
            array(self::LEVEL_0, 'foo[bar]', 'address', 'address', 'address', 'street', '[street]', 'data.foo.bar[street]'),
            array(self::LEVEL_0, 'foo[bar]', 'address', 'address', 'address', 'street', '[street]', 'data.foo.bar[street].prop'),
            array(self::LEVEL_1, 'foo[bar]', 'address', 'address', 'address', 'street', '[street]', 'data.foo[bar].street'),
            array(self::LEVEL_1, 'foo[bar]', 'address', 'address', 'address', 'street', '[street]', 'data.foo[bar].street.prop'),
            array(self::LEVEL_2, 'foo[bar]', 'address', 'address', 'address', 'street', '[street]', 'data.foo[bar][street]'),
            array(self::LEVEL_2, 'foo[bar]', 'address', 'address', 'address', 'street', '[street]', 'data.foo[bar][street].prop'),
            array(self::LEVEL_0, 'foo[bar]', 'address', 'address', 'address', 'street', '[street]', 'data[foo].bar.street'),
            array(self::LEVEL_0, 'foo[bar]', 'address', 'address', 'address', 'street', '[street]', 'data[foo].bar.street.prop'),
            array(self::LEVEL_0, 'foo[bar]', 'address', 'address', 'address', 'street', '[street]', 'data[foo].bar[street]'),
            array(self::LEVEL_0, 'foo[bar]', 'address', 'address', 'address', 'street', '[street]', 'data[foo].bar[street].prop'),
            array(self::LEVEL_0, 'foo[bar]', 'address', 'address', 'address', 'street', '[street]', 'data[foo][bar].street'),
            array(self::LEVEL_0, 'foo[bar]', 'address', 'address', 'address', 'street', '[street]', 'data[foo][bar].street.prop'),
            array(self::LEVEL_0, 'foo[bar]', 'address', 'address', 'address', 'street', '[street]', 'data[foo][bar][street]'),
            array(self::LEVEL_0, 'foo[bar]', 'address', 'address', 'address', 'street', '[street]', 'data[foo][bar][street].prop'),

            array(self::LEVEL_0, '[foo].bar', 'address', 'address', 'address', 'street', 'street', 'data.foo.bar.street'),
            array(self::LEVEL_0, '[foo].bar', 'address', 'address', 'address', 'street', 'street', 'data.foo.bar.street.prop'),
            array(self::LEVEL_0, '[foo].bar', 'address', 'address', 'address', 'street', 'street', 'data.foo.bar[street]'),
            array(self::LEVEL_0, '[foo].bar', 'address', 'address', 'address', 'street', 'street', 'data.foo.bar[street].prop'),
            array(self::LEVEL_0, '[foo].bar', 'address', 'address', 'address', 'street', 'street', 'data.foo[bar].street'),
            array(self::LEVEL_0, '[foo].bar', 'address', 'address', 'address', 'street', 'street', 'data.foo[bar].street.prop'),
            array(self::LEVEL_0, '[foo].bar', 'address', 'address', 'address', 'street', 'street', 'data.foo[bar][street]'),
            array(self::LEVEL_0, '[foo].bar', 'address', 'address', 'address', 'street', 'street', 'data.foo[bar][street].prop'),
            array(self::LEVEL_2, '[foo].bar', 'address', 'address', 'address', 'street', 'street', 'data[foo].bar.street'),
            array(self::LEVEL_2, '[foo].bar', 'address', 'address', 'address', 'street', 'street', 'data[foo].bar.street.prop'),
            array(self::LEVEL_1, '[foo].bar', 'address', 'address', 'address', 'street', 'street', 'data[foo].bar[street]'),
            array(self::LEVEL_1, '[foo].bar', 'address', 'address', 'address', 'street', 'street', 'data[foo].bar[street].prop'),
            array(self::LEVEL_0, '[foo].bar', 'address', 'address', 'address', 'street', 'street', 'data[foo][bar].street'),
            array(self::LEVEL_0, '[foo].bar', 'address', 'address', 'address', 'street', 'street', 'data[foo][bar].street.prop'),
            array(self::LEVEL_0, '[foo].bar', 'address', 'address', 'address', 'street', 'street', 'data[foo][bar][street]'),
            array(self::LEVEL_0, '[foo].bar', 'address', 'address', 'address', 'street', 'street', 'data[foo][bar][street].prop'),

            array(self::LEVEL_0, '[foo].bar', 'address', 'address', 'address', 'street', '[street]', 'data.foo.bar.street'),
            array(self::LEVEL_0, '[foo].bar', 'address', 'address', 'address', 'street', '[street]', 'data.foo.bar.street.prop'),
            array(self::LEVEL_0, '[foo].bar', 'address', 'address', 'address', 'street', '[street]', 'data.foo.bar[street]'),
            array(self::LEVEL_0, '[foo].bar', 'address', 'address', 'address', 'street', '[street]', 'data.foo.bar[street].prop'),
            array(self::LEVEL_0, '[foo].bar', 'address', 'address', 'address', 'street', '[street]', 'data.foo[bar].street'),
            array(self::LEVEL_0, '[foo].bar', 'address', 'address', 'address', 'street', '[street]', 'data.foo[bar].street.prop'),
            array(self::LEVEL_0, '[foo].bar', 'address', 'address', 'address', 'street', '[street]', 'data.foo[bar][street]'),
            array(self::LEVEL_0, '[foo].bar', 'address', 'address', 'address', 'street', '[street]', 'data.foo[bar][street].prop'),
            array(self::LEVEL_1, '[foo].bar', 'address', 'address', 'address', 'street', '[street]', 'data[foo].bar.street'),
            array(self::LEVEL_1, '[foo].bar', 'address', 'address', 'address', 'street', '[street]', 'data[foo].bar.street.prop'),
            array(self::LEVEL_2, '[foo].bar', 'address', 'address', 'address', 'street', '[street]', 'data[foo].bar[street]'),
            array(self::LEVEL_2, '[foo].bar', 'address', 'address', 'address', 'street', '[street]', 'data[foo].bar[street].prop'),
            array(self::LEVEL_0, '[foo].bar', 'address', 'address', 'address', 'street', '[street]', 'data[foo][bar].street'),
            array(self::LEVEL_0, '[foo].bar', 'address', 'address', 'address', 'street', '[street]', 'data[foo][bar].street.prop'),
            array(self::LEVEL_0, '[foo].bar', 'address', 'address', 'address', 'street', '[street]', 'data[foo][bar][street]'),
            array(self::LEVEL_0, '[foo].bar', 'address', 'address', 'address', 'street', '[street]', 'data[foo][bar][street].prop'),

            array(self::LEVEL_0, '[foo][bar]', 'address', 'address', 'address', 'street', 'street', 'data.foo.bar.street'),
            array(self::LEVEL_0, '[foo][bar]', 'address', 'address', 'address', 'street', 'street', 'data.foo.bar.street.prop'),
            array(self::LEVEL_0, '[foo][bar]', 'address', 'address', 'address', 'street', 'street', 'data.foo.bar[street]'),
            array(self::LEVEL_0, '[foo][bar]', 'address', 'address', 'address', 'street', 'street', 'data.foo.bar[street].prop'),
            array(self::LEVEL_0, '[foo][bar]', 'address', 'address', 'address', 'street', 'street', 'data.foo[bar].street'),
            array(self::LEVEL_0, '[foo][bar]', 'address', 'address', 'address', 'street', 'street', 'data.foo[bar].street.prop'),
            array(self::LEVEL_0, '[foo][bar]', 'address', 'address', 'address', 'street', 'street', 'data.foo[bar][street]'),
            array(self::LEVEL_0, '[foo][bar]', 'address', 'address', 'address', 'street', 'street', 'data.foo[bar][street].prop'),
            array(self::LEVEL_0, '[foo][bar]', 'address', 'address', 'address', 'street', 'street', 'data[foo].bar.street'),
            array(self::LEVEL_0, '[foo][bar]', 'address', 'address', 'address', 'street', 'street', 'data[foo].bar.street.prop'),
            array(self::LEVEL_0, '[foo][bar]', 'address', 'address', 'address', 'street', 'street', 'data[foo].bar[street]'),
            array(self::LEVEL_0, '[foo][bar]', 'address', 'address', 'address', 'street', 'street', 'data[foo].bar[street].prop'),
            array(self::LEVEL_2, '[foo][bar]', 'address', 'address', 'address', 'street', 'street', 'data[foo][bar].street'),
            array(self::LEVEL_2, '[foo][bar]', 'address', 'address', 'address', 'street', 'street', 'data[foo][bar].street.prop'),
            array(self::LEVEL_1, '[foo][bar]', 'address', 'address', 'address', 'street', 'street', 'data[foo][bar][street]'),
            array(self::LEVEL_1, '[foo][bar]', 'address', 'address', 'address', 'street', 'street', 'data[foo][bar][street].prop'),

            array(self::LEVEL_0, '[foo][bar]', 'address', 'address', 'address', 'street', '[street]', 'data.foo.bar.street'),
            array(self::LEVEL_0, '[foo][bar]', 'address', 'address', 'address', 'street', '[street]', 'data.foo.bar.street.prop'),
            array(self::LEVEL_0, '[foo][bar]', 'address', 'address', 'address', 'street', '[street]', 'data.foo.bar[street]'),
            array(self::LEVEL_0, '[foo][bar]', 'address', 'address', 'address', 'street', '[street]', 'data.foo.bar[street].prop'),
            array(self::LEVEL_0, '[foo][bar]', 'address', 'address', 'address', 'street', '[street]', 'data.foo[bar].street'),
            array(self::LEVEL_0, '[foo][bar]', 'address', 'address', 'address', 'street', '[street]', 'data.foo[bar].street.prop'),
            array(self::LEVEL_0, '[foo][bar]', 'address', 'address', 'address', 'street', '[street]', 'data.foo[bar][street]'),
            array(self::LEVEL_0, '[foo][bar]', 'address', 'address', 'address', 'street', '[street]', 'data.foo[bar][street].prop'),
            array(self::LEVEL_0, '[foo][bar]', 'address', 'address', 'address', 'street', '[street]', 'data[foo].bar.street'),
            array(self::LEVEL_0, '[foo][bar]', 'address', 'address', 'address', 'street', '[street]', 'data[foo].bar.street.prop'),
            array(self::LEVEL_0, '[foo][bar]', 'address', 'address', 'address', 'street', '[street]', 'data[foo].bar[street]'),
            array(self::LEVEL_0, '[foo][bar]', 'address', 'address', 'address', 'street', '[street]', 'data[foo].bar[street].prop'),
            array(self::LEVEL_1, '[foo][bar]', 'address', 'address', 'address', 'street', '[street]', 'data[foo][bar].street'),
            array(self::LEVEL_1, '[foo][bar]', 'address', 'address', 'address', 'street', '[street]', 'data[foo][bar].street.prop'),
            array(self::LEVEL_2, '[foo][bar]', 'address', 'address', 'address', 'street', '[street]', 'data[foo][bar][street]'),
            array(self::LEVEL_2, '[foo][bar]', 'address', 'address', 'address', 'street', '[street]', 'data[foo][bar][street].prop'),

            array(self::LEVEL_2, 'foo', 'address.street', 'address', 'address', 'street', 'street', 'data.foo'),
            array(self::LEVEL_2, 'foo', 'address.street', 'address', 'address', 'street', 'street', 'data.foo.prop'),
            array(self::LEVEL_2, '[foo]', 'address.street', 'address', 'address', 'street', 'street', 'data[foo]'),
            array(self::LEVEL_2, '[foo]', 'address.street', 'address', 'address', 'street', 'street', 'data[foo].prop'),

            array(self::LEVEL_2, 'foo', 'address.street', 'address', 'address', 'street', '[street]', 'data.foo'),
            array(self::LEVEL_2, 'foo', 'address.street', 'address', 'address', 'street', '[street]', 'data.foo.prop'),
            array(self::LEVEL_2, '[foo]', 'address.street', 'address', 'address', 'street', '[street]', 'data[foo]'),
            array(self::LEVEL_2, '[foo]', 'address.street', 'address', 'address', 'street', '[street]', 'data[foo].prop'),

            array(self::LEVEL_2, 'foo', 'address.street', 'address', '[address]', 'street', 'street', 'data.foo'),
            array(self::LEVEL_2, 'foo', 'address.street', 'address', '[address]', 'street', 'street', 'data.foo.prop'),
            array(self::LEVEL_2, '[foo]', 'address.street', 'address', '[address]', 'street', 'street', 'data[foo]'),
            array(self::LEVEL_2, '[foo]', 'address.street', 'address', '[address]', 'street', 'street', 'data[foo].prop'),

            array(self::LEVEL_2, 'foo.bar', 'address.street', 'address', 'address', 'street', 'street', 'data.foo.bar'),
            array(self::LEVEL_2, 'foo.bar', 'address.street', 'address', 'address', 'street', 'street', 'data.foo.bar.prop'),
            array(self::LEVEL_2, 'foo[bar]', 'address.street', 'address', 'address', 'street', 'street', 'data.foo[bar]'),
            array(self::LEVEL_2, 'foo[bar]', 'address.street', 'address', 'address', 'street', 'street', 'data.foo[bar].prop'),
            array(self::LEVEL_2, '[foo].bar', 'address.street', 'address', 'address', 'street', 'street', 'data[foo].bar'),
            array(self::LEVEL_2, '[foo].bar', 'address.street', 'address', 'address', 'street', 'street', 'data[foo].bar.prop'),
            array(self::LEVEL_2, '[foo][bar]', 'address.street', 'address', 'address', 'street', 'street', 'data[foo][bar]'),
            array(self::LEVEL_2, '[foo][bar]', 'address.street', 'address', 'address', 'street', 'street', 'data[foo][bar].prop'),

            array(self::LEVEL_2, 'foo.bar', 'address.street', 'address', 'address', 'street', '[street]', 'data.foo.bar'),
            array(self::LEVEL_2, 'foo.bar', 'address.street', 'address', 'address', 'street', '[street]', 'data.foo.bar.prop'),
            array(self::LEVEL_2, 'foo[bar]', 'address.street', 'address', 'address', 'street', '[street]', 'data.foo[bar]'),
            array(self::LEVEL_2, 'foo[bar]', 'address.street', 'address', 'address', 'street', '[street]', 'data.foo[bar].prop'),
            array(self::LEVEL_2, '[foo].bar', 'address.street', 'address', 'address', 'street', '[street]', 'data[foo].bar'),
            array(self::LEVEL_2, '[foo].bar', 'address.street', 'address', 'address', 'street', '[street]', 'data[foo].bar.prop'),
            array(self::LEVEL_2, '[foo][bar]', 'address.street', 'address', 'address', 'street', '[street]', 'data[foo][bar]'),
            array(self::LEVEL_2, '[foo][bar]', 'address.street', 'address', 'address', 'street', '[street]', 'data[foo][bar].prop'),

            array(self::LEVEL_2, 'foo.bar', 'address.street', 'address', '[address]', 'street', 'street', 'data.foo.bar'),
            array(self::LEVEL_2, 'foo.bar', 'address.street', 'address', '[address]', 'street', 'street', 'data.foo.bar.prop'),
            array(self::LEVEL_2, 'foo[bar]', 'address.street', 'address', '[address]', 'street', 'street', 'data.foo[bar]'),
            array(self::LEVEL_2, 'foo[bar]', 'address.street', 'address', '[address]', 'street', 'street', 'data.foo[bar].prop'),
            array(self::LEVEL_2, '[foo].bar', 'address.street', 'address', '[address]', 'street', 'street', 'data[foo].bar'),
            array(self::LEVEL_2, '[foo].bar', 'address.street', 'address', '[address]', 'street', 'street', 'data[foo].bar.prop'),
            array(self::LEVEL_2, '[foo][bar]', 'address.street', 'address', '[address]', 'street', 'street', 'data[foo][bar]'),
            array(self::LEVEL_2, '[foo][bar]', 'address.street', 'address', '[address]', 'street', 'street', 'data[foo][bar].prop'),

            // Edge cases
            array(self::LEVEL_2, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data.foo.street'),
            array(self::LEVEL_2, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data.foo.street.prop'),
            array(self::LEVEL_1, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data.foo[street]'),
            array(self::LEVEL_1, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data.foo[street].prop'),
            array(self::LEVEL_0, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data[foo].street'),
            array(self::LEVEL_0, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data[foo].street.prop'),
            array(self::LEVEL_0, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data[foo][street]'),
            array(self::LEVEL_0, 'foo', 'address', 'address', '[address]', 'street', 'street', 'data[foo][street].prop'),

            array(self::LEVEL_0, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data.foo.street'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data.foo.street.prop'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data.foo[street]'),
            array(self::LEVEL_0, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data.foo[street].prop'),
            array(self::LEVEL_2, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data[foo].street'),
            array(self::LEVEL_2, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data[foo].street.prop'),
            array(self::LEVEL_1, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data[foo][street]'),
            array(self::LEVEL_1, '[foo]', 'address', 'address', 'address', 'street', 'street', 'data[foo][street].prop'),
        );
    }

    /**
     * @dataProvider provideCustomDataErrorTests
     */
    public function testCustomDataErrorMapping($target, $mapFrom, $mapTo, $childName, $childPath, $grandChildName, $grandChildPath, $violationPath)
    {
        $violation = $this->getConstraintViolation($violationPath);
        $parent = $this->getForm('parent', null, null, array($mapFrom => $mapTo));
        $child = $this->getForm($childName, $childPath);
        $grandChild = $this->getForm($grandChildName, $grandChildPath);

        $parent->add($child);
        $child->add($grandChild);

        // Add a field mapped to the first element of $mapFrom
        // to try to distract the algorithm
        // Only add it if we expect the error to come up on a different
        // level than LEVEL_0, because in this case the error would
        // (correctly) be mapped to the distraction field
        if (self::LEVEL_0 !== $target) {
            $mapFromPath = new PropertyPath($mapFrom);
            $mapFromPrefix = $mapFromPath->isIndex(0)
                ? '['.$mapFromPath->getElement(0).']'
                : $mapFromPath->getElement(0);
            $distraction = $this->getForm('distraction', $mapFromPrefix);

            $parent->add($distraction);
        }

        $parent->submit(array());

        $this->mapper->mapViolation($violation, $parent);

        if (self::LEVEL_0 !== $target) {
            $this->assertCount(0, $distraction->getErrors(), 'distraction should not have an error, but has one');
        }

        if (self::LEVEL_0 === $target) {
            $this->assertEquals(array($this->getFormError($violation, $parent)), iterator_to_array($parent->getErrors()), $parent->getName().' should have an error, but has none');
            $this->assertCount(0, $child->getErrors(), $childName.' should not have an error, but has one');
            $this->assertCount(0, $grandChild->getErrors(), $grandChildName.' should not have an error, but has one');
        } elseif (self::LEVEL_1 === $target) {
            $this->assertCount(0, $parent->getErrors(), $parent->getName().' should not have an error, but has one');
            $this->assertEquals(array($this->getFormError($violation, $child)), iterator_to_array($child->getErrors()), $childName.' should have an error, but has none');
            $this->assertCount(0, $grandChild->getErrors(), $grandChildName.' should not have an error, but has one');
        } else {
            $this->assertCount(0, $parent->getErrors(), $parent->getName().' should not have an error, but has one');
            $this->assertCount(0, $child->getErrors(), $childName.' should not have an error, but has one');
            $this->assertEquals(array($this->getFormError($violation, $grandChild)), iterator_to_array($grandChild->getErrors()), $grandChildName.' should have an error, but has none');
        }
    }

    public function provideCustomFormErrorTests()
    {
        // This case is different than the data errors, because here the
        // left side of the mapping refers to the property path of the actual
        // children. In other words, a child error only works if
        // 1) the error actually maps to an existing child and
        // 2) the property path of that child (relative to the form providing
        //    the mapping) matches the left side of the mapping
        return array(
            // mapping target, map from, map to, child name, its property path, grand child name, its property path, violation path
            array(self::LEVEL_1, 'foo', 'address', 'foo', 'foo', 'address', 'address', 'street', 'street', 'children[foo].children[street].data'),
            array(self::LEVEL_1, 'foo', 'address', 'foo', 'foo', 'address', 'address', 'street', 'street', 'children[foo].children[street].data.prop'),
            array(self::LEVEL_2, 'foo', 'address', 'foo', 'foo', 'address', 'address', 'street', 'street', 'children[foo].data.street'),
            array(self::LEVEL_2, 'foo', 'address', 'foo', 'foo', 'address', 'address', 'street', 'street', 'children[foo].data.street.prop'),
            array(self::LEVEL_1, 'foo', 'address', 'foo', 'foo', 'address', 'address', 'street', 'street', 'children[foo].data[street]'),
            array(self::LEVEL_1, 'foo', 'address', 'foo', 'foo', 'address', 'address', 'street', 'street', 'children[foo].data[street].prop'),

            array(self::LEVEL_2, 'foo', 'address', 'foo', 'foo', 'address', 'address', 'street', 'street', 'children[address].children[street].data'),
            array(self::LEVEL_2, 'foo', 'address', 'foo', 'foo', 'address', 'address', 'street', 'street', 'children[address].children[street].data.prop'),
            array(self::LEVEL_2, 'foo', 'address', 'foo', 'foo', 'address', 'address', 'street', 'street', 'children[address].data.street'),
            array(self::LEVEL_2, 'foo', 'address', 'foo', 'foo', 'address', 'address', 'street', 'street', 'children[address].data.street.prop'),
            array(self::LEVEL_1, 'foo', 'address', 'foo', 'foo', 'address', 'address', 'street', 'street', 'children[address].data[street]'),
            array(self::LEVEL_1, 'foo', 'address', 'foo', 'foo', 'address', 'address', 'street', 'street', 'children[address].data[street].prop'),

            // Property path of the erroneous field and mapping must match exactly
            array(self::LEVEL_1B, 'foo', 'address', 'foo', '[foo]', 'address', 'address', 'street', 'street', 'children[foo].children[street].data'),
            array(self::LEVEL_1B, 'foo', 'address', 'foo', '[foo]', 'address', 'address', 'street', 'street', 'children[foo].children[street].data.prop'),
            array(self::LEVEL_1B, 'foo', 'address', 'foo', '[foo]', 'address', 'address', 'street', 'street', 'children[foo].data.street'),
            array(self::LEVEL_1B, 'foo', 'address', 'foo', '[foo]', 'address', 'address', 'street', 'street', 'children[foo].data.street.prop'),
            array(self::LEVEL_1B, 'foo', 'address', 'foo', '[foo]', 'address', 'address', 'street', 'street', 'children[foo].data[street]'),
            array(self::LEVEL_1B, 'foo', 'address', 'foo', '[foo]', 'address', 'address', 'street', 'street', 'children[foo].data[street].prop'),

            array(self::LEVEL_1B, '[foo]', 'address', 'foo', 'foo', 'address', 'address', 'street', 'street', 'children[foo].children[street].data'),
            array(self::LEVEL_1B, '[foo]', 'address', 'foo', 'foo', 'address', 'address', 'street', 'street', 'children[foo].children[street].data.prop'),
            array(self::LEVEL_1B, '[foo]', 'address', 'foo', 'foo', 'address', 'address', 'street', 'street', 'children[foo].data.street'),
            array(self::LEVEL_1B, '[foo]', 'address', 'foo', 'foo', 'address', 'address', 'street', 'street', 'children[foo].data.street.prop'),
            array(self::LEVEL_1B, '[foo]', 'address', 'foo', 'foo', 'address', 'address', 'street', 'street', 'children[foo].data[street]'),
            array(self::LEVEL_1B, '[foo]', 'address', 'foo', 'foo', 'address', 'address', 'street', 'street', 'children[foo].data[street].prop'),

            array(self::LEVEL_1, '[foo]', 'address', 'foo', '[foo]', 'address', 'address', 'street', 'street', 'children[foo].children[street].data'),
            array(self::LEVEL_1, '[foo]', 'address', 'foo', '[foo]', 'address', 'address', 'street', 'street', 'children[foo].children[street].data.prop'),
            array(self::LEVEL_2, '[foo]', 'address', 'foo', '[foo]', 'address', 'address', 'street', 'street', 'children[foo].data.street'),
            array(self::LEVEL_2, '[foo]', 'address', 'foo', '[foo]', 'address', 'address', 'street', 'street', 'children[foo].data.street.prop'),
            array(self::LEVEL_1, '[foo]', 'address', 'foo', '[foo]', 'address', 'address', 'street', 'street', 'children[foo].data[street]'),
            array(self::LEVEL_1, '[foo]', 'address', 'foo', '[foo]', 'address', 'address', 'street', 'street', 'children[foo].data[street].prop'),

            array(self::LEVEL_2, 'foo', 'address', 'foo', 'foo', 'address', 'address', 'street', '[street]', 'children[foo].children[street].data'),
            array(self::LEVEL_2, 'foo', 'address', 'foo', 'foo', 'address', 'address', 'street', '[street]', 'children[foo].children[street].data.prop'),
            array(self::LEVEL_1, 'foo', 'address', 'foo', 'foo', 'address', 'address', 'street', '[street]', 'children[foo].data.street'),
            array(self::LEVEL_1, 'foo', 'address', 'foo', 'foo', 'address', 'address', 'street', '[street]', 'children[foo].data.street.prop'),
            array(self::LEVEL_2, 'foo', 'address', 'foo', 'foo', 'address', 'address', 'street', '[street]', 'children[foo].data[street]'),
            array(self::LEVEL_2, 'foo', 'address', 'foo', 'foo', 'address', 'address', 'street', '[street]', 'children[foo].data[street].prop'),

            array(self::LEVEL_2, 'foo', 'address', 'foo', 'foo', 'address', 'address', 'street', '[street]', 'children[address].children[street].data'),
            array(self::LEVEL_2, 'foo', 'address', 'foo', 'foo', 'address', 'address', 'street', '[street]', 'children[address].children[street].data.prop'),
            array(self::LEVEL_1, 'foo', 'address', 'foo', 'foo', 'address', 'address', 'street', '[street]', 'children[address].data.street'),
            array(self::LEVEL_1, 'foo', 'address', 'foo', 'foo', 'address', 'address', 'street', '[street]', 'children[address].data.street.prop'),
            array(self::LEVEL_2, 'foo', 'address', 'foo', 'foo', 'address', 'address', 'street', '[street]', 'children[address].data[street]'),
            array(self::LEVEL_2, 'foo', 'address', 'foo', 'foo', 'address', 'address', 'street', '[street]', 'children[address].data[street].prop'),

            array(self::LEVEL_1, 'foo', 'address', 'foo', 'foo', 'address', '[address]', 'street', 'street', 'children[foo].children[street].data'),
            array(self::LEVEL_1, 'foo', 'address', 'foo', 'foo', 'address', '[address]', 'street', 'street', 'children[foo].children[street].data.prop'),
            array(self::LEVEL_2, 'foo', 'address', 'foo', 'foo', 'address', '[address]', 'street', 'street', 'children[foo].data.street'),
            array(self::LEVEL_2, 'foo', 'address', 'foo', 'foo', 'address', '[address]', 'street', 'street', 'children[foo].data.street.prop'),
            array(self::LEVEL_1, 'foo', 'address', 'foo', 'foo', 'address', '[address]', 'street', 'street', 'children[foo].data[street]'),
            array(self::LEVEL_1, 'foo', 'address', 'foo', 'foo', 'address', '[address]', 'street', 'street', 'children[foo].data[street].prop'),

            array(self::LEVEL_2, 'foo', 'address', 'foo', 'foo', 'address', '[address]', 'street', 'street', 'children[address].children[street].data'),
            array(self::LEVEL_2, 'foo', 'address', 'foo', 'foo', 'address', '[address]', 'street', 'street', 'children[address].children[street].data.prop'),
            array(self::LEVEL_2, 'foo', 'address', 'foo', 'foo', 'address', '[address]', 'street', 'street', 'children[address].data.street'),
            array(self::LEVEL_2, 'foo', 'address', 'foo', 'foo', 'address', '[address]', 'street', 'street', 'children[address].data.street.prop'),
            array(self::LEVEL_1, 'foo', 'address', 'foo', 'foo', 'address', '[address]', 'street', 'street', 'children[address].data[street]'),
            array(self::LEVEL_1, 'foo', 'address', 'foo', 'foo', 'address', '[address]', 'street', 'street', 'children[address].data[street].prop'),

            array(self::LEVEL_2, 'foo', 'address', 'foo', 'foo', 'address', '[address]', 'street', '[street]', 'children[foo].children[street].data'),
            array(self::LEVEL_2, 'foo', 'address', 'foo', 'foo', 'address', '[address]', 'street', '[street]', 'children[foo].children[street].data.prop'),
            array(self::LEVEL_1, 'foo', 'address', 'foo', 'foo', 'address', '[address]', 'street', '[street]', 'children[foo].data.street'),
            array(self::LEVEL_1, 'foo', 'address', 'foo', 'foo', 'address', '[address]', 'street', '[street]', 'children[foo].data.street.prop'),
            array(self::LEVEL_2, 'foo', 'address', 'foo', 'foo', 'address', '[address]', 'street', '[street]', 'children[foo].data[street]'),
            array(self::LEVEL_2, 'foo', 'address', 'foo', 'foo', 'address', '[address]', 'street', '[street]', 'children[foo].data[street].prop'),

            array(self::LEVEL_2, 'foo', 'address', 'foo', 'foo', 'address', '[address]', 'street', '[street]', 'children[address].children[street].data'),
            array(self::LEVEL_2, 'foo', 'address', 'foo', 'foo', 'address', '[address]', 'street', '[street]', 'children[address].children[street].data.prop'),
            array(self::LEVEL_1, 'foo', 'address', 'foo', 'foo', 'address', '[address]', 'street', '[street]', 'children[address].data.street'),
            array(self::LEVEL_1, 'foo', 'address', 'foo', 'foo', 'address', '[address]', 'street', '[street]', 'children[address].data.street.prop'),
            array(self::LEVEL_2, 'foo', 'address', 'foo', 'foo', 'address', '[address]', 'street', '[street]', 'children[address].data[street]'),
            array(self::LEVEL_2, 'foo', 'address', 'foo', 'foo', 'address', '[address]', 'street', '[street]', 'children[address].data[street].prop'),

            // Map to a nested child
            array(self::LEVEL_2, 'foo', 'address.street', 'foo', 'foo', 'address', 'address', 'street', 'street', 'children[foo]'),
            array(self::LEVEL_2, 'foo', 'address.street', 'foo', 'foo', 'address', 'address', 'street', '[street]', 'children[foo]'),
            array(self::LEVEL_2, 'foo', 'address.street', 'foo', 'foo', 'address', '[address]', 'street', 'street', 'children[foo]'),
            array(self::LEVEL_2, 'foo', 'address.street', 'foo', 'foo', 'address', '[address]', 'street', '[street]', 'children[foo]'),

            // Map from a nested child
            array(self::LEVEL_1B, 'address.street', 'foo', 'foo', 'foo', 'address', 'address', 'street', 'street', 'children[address].children[street]'),
            array(self::LEVEL_1B, 'address.street', 'foo', 'foo', 'foo', 'address', 'address', 'street', 'street', 'children[address].data.street'),
            array(self::LEVEL_1, 'address.street', 'foo', 'foo', 'foo', 'address', 'address', 'street', 'street', 'children[address].data[street]'),
            array(self::LEVEL_2, 'address.street', 'foo', 'foo', 'foo', 'address', 'address', 'street', '[street]', 'children[address].children[street]'),
            array(self::LEVEL_1B, 'address.street', 'foo', 'foo', 'foo', 'address', 'address', 'street', '[street]', 'children[address].data.street'),
            array(self::LEVEL_2, 'address.street', 'foo', 'foo', 'foo', 'address', 'address', 'street', '[street]', 'children[address].data[street]'),
            array(self::LEVEL_2, 'address.street', 'foo', 'foo', 'foo', 'address', '[address]', 'street', 'street', 'children[address].children[street]'),
            array(self::LEVEL_2, 'address.street', 'foo', 'foo', 'foo', 'address', '[address]', 'street', 'street', 'children[address].data.street'),
            array(self::LEVEL_1, 'address.street', 'foo', 'foo', 'foo', 'address', '[address]', 'street', 'street', 'children[address].data[street]'),
            array(self::LEVEL_2, 'address.street', 'foo', 'foo', 'foo', 'address', '[address]', 'street', '[street]', 'children[address].children[street]'),
            array(self::LEVEL_1, 'address.street', 'foo', 'foo', 'foo', 'address', '[address]', 'street', '[street]', 'children[address].data.street'),
            array(self::LEVEL_2, 'address.street', 'foo', 'foo', 'foo', 'address', '[address]', 'street', '[street]', 'children[address].data[street]'),

            array(self::LEVEL_2, 'address[street]', 'foo', 'foo', 'foo', 'address', 'address', 'street', 'street', 'children[address].children[street]'),
            array(self::LEVEL_2, 'address[street]', 'foo', 'foo', 'foo', 'address', 'address', 'street', 'street', 'children[address].data.street'),
            array(self::LEVEL_1B, 'address[street]', 'foo', 'foo', 'foo', 'address', 'address', 'street', 'street', 'children[address].data[street]'),
            array(self::LEVEL_1B, 'address[street]', 'foo', 'foo', 'foo', 'address', 'address', 'street', '[street]', 'children[address].children[street]'),
            array(self::LEVEL_1, 'address[street]', 'foo', 'foo', 'foo', 'address', 'address', 'street', '[street]', 'children[address].data.street'),
            array(self::LEVEL_1B, 'address[street]', 'foo', 'foo', 'foo', 'address', 'address', 'street', '[street]', 'children[address].data[street]'),
            array(self::LEVEL_2, 'address[street]', 'foo', 'foo', 'foo', 'address', '[address]', 'street', 'street', 'children[address].children[street]'),
            array(self::LEVEL_2, 'address[street]', 'foo', 'foo', 'foo', 'address', '[address]', 'street', 'street', 'children[address].data.street'),
            array(self::LEVEL_1, 'address[street]', 'foo', 'foo', 'foo', 'address', '[address]', 'street', 'street', 'children[address].data[street]'),
            array(self::LEVEL_2, 'address[street]', 'foo', 'foo', 'foo', 'address', '[address]', 'street', '[street]', 'children[address].children[street]'),
            array(self::LEVEL_1, 'address[street]', 'foo', 'foo', 'foo', 'address', '[address]', 'street', '[street]', 'children[address].data.street'),
            array(self::LEVEL_2, 'address[street]', 'foo', 'foo', 'foo', 'address', '[address]', 'street', '[street]', 'children[address].data[street]'),

            array(self::LEVEL_2, '[address].street', 'foo', 'foo', 'foo', 'address', 'address', 'street', 'street', 'children[address].children[street]'),
            array(self::LEVEL_2, '[address].street', 'foo', 'foo', 'foo', 'address', 'address', 'street', 'street', 'children[address].data.street'),
            array(self::LEVEL_1, '[address].street', 'foo', 'foo', 'foo', 'address', 'address', 'street', 'street', 'children[address].data[street]'),
            array(self::LEVEL_2, '[address].street', 'foo', 'foo', 'foo', 'address', 'address', 'street', '[street]', 'children[address].children[street]'),
            array(self::LEVEL_1, '[address].street', 'foo', 'foo', 'foo', 'address', 'address', 'street', '[street]', 'children[address].data.street'),
            array(self::LEVEL_2, '[address].street', 'foo', 'foo', 'foo', 'address', 'address', 'street', '[street]', 'children[address].data[street]'),
            array(self::LEVEL_1B, '[address].street', 'foo', 'foo', 'foo', 'address', '[address]', 'street', 'street', 'children[address].children[street]'),
            array(self::LEVEL_1B, '[address].street', 'foo', 'foo', 'foo', 'address', '[address]', 'street', 'street', 'children[address].data.street'),
            array(self::LEVEL_1, '[address].street', 'foo', 'foo', 'foo', 'address', '[address]', 'street', 'street', 'children[address].data[street]'),
            array(self::LEVEL_2, '[address].street', 'foo', 'foo', 'foo', 'address', '[address]', 'street', '[street]', 'children[address].children[street]'),
            array(self::LEVEL_1B, '[address].street', 'foo', 'foo', 'foo', 'address', '[address]', 'street', '[street]', 'children[address].data.street'),
            array(self::LEVEL_2, '[address].street', 'foo', 'foo', 'foo', 'address', '[address]', 'street', '[street]', 'children[address].data[street]'),

            array(self::LEVEL_2, '[address][street]', 'foo', 'foo', 'foo', 'address', 'address', 'street', 'street', 'children[address].children[street]'),
            array(self::LEVEL_2, '[address][street]', 'foo', 'foo', 'foo', 'address', 'address', 'street', 'street', 'children[address].data.street'),
            array(self::LEVEL_1, '[address][street]', 'foo', 'foo', 'foo', 'address', 'address', 'street', 'street', 'children[address].data[street]'),
            array(self::LEVEL_2, '[address][street]', 'foo', 'foo', 'foo', 'address', 'address', 'street', '[street]', 'children[address].children[street]'),
            array(self::LEVEL_1, '[address][street]', 'foo', 'foo', 'foo', 'address', 'address', 'street', '[street]', 'children[address].data.street'),
            array(self::LEVEL_2, '[address][street]', 'foo', 'foo', 'foo', 'address', 'address', 'street', '[street]', 'children[address].data[street]'),
            array(self::LEVEL_2, '[address][street]', 'foo', 'foo', 'foo', 'address', '[address]', 'street', 'street', 'children[address].children[street]'),
            array(self::LEVEL_2, '[address][street]', 'foo', 'foo', 'foo', 'address', '[address]', 'street', 'street', 'children[address].data.street'),
            array(self::LEVEL_1B, '[address][street]', 'foo', 'foo', 'foo', 'address', '[address]', 'street', 'street', 'children[address].data[street]'),
            array(self::LEVEL_1B, '[address][street]', 'foo', 'foo', 'foo', 'address', '[address]', 'street', '[street]', 'children[address].children[street]'),
            array(self::LEVEL_1, '[address][street]', 'foo', 'foo', 'foo', 'address', '[address]', 'street', '[street]', 'children[address].data.street'),
            array(self::LEVEL_1B, '[address][street]', 'foo', 'foo', 'foo', 'address', '[address]', 'street', '[street]', 'children[address].data[street]'),
        );
    }

    /**
     * @dataProvider provideCustomFormErrorTests
     */
    public function testCustomFormErrorMapping($target, $mapFrom, $mapTo, $errorName, $errorPath, $childName, $childPath, $grandChildName, $grandChildPath, $violationPath)
    {
        $violation = $this->getConstraintViolation($violationPath);
        $parent = $this->getForm('parent', null, null, array($mapFrom => $mapTo));
        $child = $this->getForm($childName, $childPath);
        $grandChild = $this->getForm($grandChildName, $grandChildPath);
        $errorChild = $this->getForm($errorName, $errorPath);

        $parent->add($child);
        $parent->add($errorChild);
        $child->add($grandChild);

        $parent->submit(array());

        $this->mapper->mapViolation($violation, $parent);

        if (self::LEVEL_0 === $target) {
            $this->assertCount(0, $errorChild->getErrors(), $errorName.' should not have an error, but has one');
            $this->assertEquals(array($this->getFormError($violation, $parent)), iterator_to_array($parent->getErrors()), $parent->getName().' should have an error, but has none');
            $this->assertCount(0, $child->getErrors(), $childName.' should not have an error, but has one');
            $this->assertCount(0, $grandChild->getErrors(), $grandChildName.' should not have an error, but has one');
        } elseif (self::LEVEL_1 === $target) {
            $this->assertCount(0, $errorChild->getErrors(), $errorName.' should not have an error, but has one');
            $this->assertCount(0, $parent->getErrors(), $parent->getName().' should not have an error, but has one');
            $this->assertEquals(array($this->getFormError($violation, $child)), iterator_to_array($child->getErrors()), $childName.' should have an error, but has none');
            $this->assertCount(0, $grandChild->getErrors(), $grandChildName.' should not have an error, but has one');
        } elseif (self::LEVEL_1B === $target) {
            $this->assertEquals(array($this->getFormError($violation, $errorChild)), iterator_to_array($errorChild->getErrors()), $errorName.' should have an error, but has none');
            $this->assertCount(0, $parent->getErrors(), $parent->getName().' should not have an error, but has one');
            $this->assertCount(0, $child->getErrors(), $childName.' should not have an error, but has one');
            $this->assertCount(0, $grandChild->getErrors(), $grandChildName.' should not have an error, but has one');
        } else {
            $this->assertCount(0, $errorChild->getErrors(), $errorName.' should not have an error, but has one');
            $this->assertCount(0, $parent->getErrors(), $parent->getName().' should not have an error, but has one');
            $this->assertCount(0, $child->getErrors(), $childName.' should not have an error, but has one');
            $this->assertEquals(array($this->getFormError($violation, $grandChild)), iterator_to_array($grandChild->getErrors()), $grandChildName.' should have an error, but has none');
        }
    }

    public function provideErrorTestsForFormInheritingParentData()
    {
        return array(
            // mapping target, child name, its property path, grand child name, its property path, violation path
            array(self::LEVEL_2, 'address', 'address', 'street', 'street', 'children[address].children[street].data'),
            array(self::LEVEL_2, 'address', 'address', 'street', 'street', 'children[address].children[street].data.prop'),
            array(self::LEVEL_2, 'address', 'address', 'street', 'street', 'children[address].data.street'),
            array(self::LEVEL_2, 'address', 'address', 'street', 'street', 'children[address].data.street.prop'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'street', 'children[address].data[street]'),
            array(self::LEVEL_1, 'address', 'address', 'street', 'street', 'children[address].data[street].prop'),
            array(self::LEVEL_2, 'address', 'address', 'street', 'street', 'data.street'),
            array(self::LEVEL_2, 'address', 'address', 'street', 'street', 'data.street.prop'),
            array(self::LEVEL_0, 'address', 'address', 'street', 'street', 'data[street]'),
            array(self::LEVEL_0, 'address', 'address', 'street', 'street', 'data[street].prop'),
            array(self::LEVEL_0, 'address', 'address', 'street', 'street', 'data.address.street'),
            array(self::LEVEL_0, 'address', 'address', 'street', 'street', 'data.address.street.prop'),
            array(self::LEVEL_0, 'address', 'address', 'street', 'street', 'data.address[street]'),
            array(self::LEVEL_0, 'address', 'address', 'street', 'street', 'data.address[street].prop'),
            array(self::LEVEL_0, 'address', 'address', 'street', 'street', 'data[address].street'),
            array(self::LEVEL_0, 'address', 'address', 'street', 'street', 'data[address].street.prop'),
            array(self::LEVEL_0, 'address', 'address', 'street', 'street', 'data[address][street]'),
            array(self::LEVEL_0, 'address', 'address', 'street', 'street', 'data[address][street].prop'),
        );
    }

    /**
     * @dataProvider provideErrorTestsForFormInheritingParentData
     */
    public function testErrorMappingForFormInheritingParentData($target, $childName, $childPath, $grandChildName, $grandChildPath, $violationPath)
    {
        $violation = $this->getConstraintViolation($violationPath);
        $parent = $this->getForm('parent');
        $child = $this->getForm($childName, $childPath, null, array(), true);
        $grandChild = $this->getForm($grandChildName, $grandChildPath);

        $parent->add($child);
        $child->add($grandChild);

        $parent->submit(array());

        $this->mapper->mapViolation($violation, $parent);

        if (self::LEVEL_0 === $target) {
            $this->assertEquals(array($this->getFormError($violation, $parent)), iterator_to_array($parent->getErrors()), $parent->getName().' should have an error, but has none');
            $this->assertCount(0, $child->getErrors(), $childName.' should not have an error, but has one');
            $this->assertCount(0, $grandChild->getErrors(), $grandChildName.' should not have an error, but has one');
        } elseif (self::LEVEL_1 === $target) {
            $this->assertCount(0, $parent->getErrors(), $parent->getName().' should not have an error, but has one');
            $this->assertEquals(array($this->getFormError($violation, $child)), iterator_to_array($child->getErrors()), $childName.' should have an error, but has none');
            $this->assertCount(0, $grandChild->getErrors(), $grandChildName.' should not have an error, but has one');
        } else {
            $this->assertCount(0, $parent->getErrors(), $parent->getName().' should not have an error, but has one');
            $this->assertCount(0, $child->getErrors(), $childName.' should not have an error, but has one');
            $this->assertEquals(array($this->getFormError($violation, $grandChild)), iterator_to_array($grandChild->getErrors()), $grandChildName.' should have an error, but has none');
        }
    }

    public function testBacktrackIfSeveralSubFormsWithSamePropertyPath()
    {
        $parent = $this->getForm('parent');
        $child1 = $this->getForm('subform1', 'address');
        $child2 = $this->getForm('subform2', 'address');
        $child3 = $this->getForm('subform3', null, null, array(), true);
        $child4 = $this->getForm('subform4', null, null, array(), true);
        $grandChild1 = $this->getForm('street');
        $grandChild2 = $this->getForm('street', '[sub_address1_street]');
        $grandChild3 = $this->getForm('street', '[sub_address2_street]');

        $parent->add($child1);
        $parent->add($child2);
        $parent->add($child3);
        $parent->add($child4);
        $child2->add($grandChild1);
        $child3->add($grandChild2);
        $child4->add($grandChild3);

        $parent->submit(array());

        $violation1 = $this->getConstraintViolation('data.address[street]');
        $violation2 = $this->getConstraintViolation('data[sub_address1_street]');
        $violation3 = $this->getConstraintViolation('data[sub_address2_street]');
        $this->mapper->mapViolation($violation1, $parent);
        $this->mapper->mapViolation($violation2, $parent);
        $this->mapper->mapViolation($violation3, $parent);

        $this->assertCount(0, $parent->getErrors(), $parent->getName().' should not have an error, but has one');
        $this->assertCount(0, $child1->getErrors(), $child1->getName().' should not have an error, but has one');
        $this->assertCount(0, $child2->getErrors(), $child2->getName().' should not have an error, but has one');
        $this->assertCount(0, $child3->getErrors(), $child3->getName().' should not have an error, but has one');
        $this->assertCount(0, $child4->getErrors(), $child4->getName().' should not have an error, but has one');
        $this->assertEquals(array($this->getFormError($violation1, $grandChild1)), iterator_to_array($grandChild1->getErrors()), $grandChild1->getName().' should have an error, but has none');
        $this->assertEquals(array($this->getFormError($violation2, $grandChild2)), iterator_to_array($grandChild2->getErrors()), $grandChild2->getName().' should have an error, but has none');
        $this->assertEquals(array($this->getFormError($violation3, $grandChild3)), iterator_to_array($grandChild3->getErrors()), $grandChild3->getName().' should have an error, but has none');
    }
}
