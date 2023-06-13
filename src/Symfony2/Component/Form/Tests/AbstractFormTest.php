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
use Symfony2\Component\EventDispatcher\EventDispatcher;
use Symfony2\Component\EventDispatcher\EventDispatcherInterface;
use Symfony2\Component\Form\FormBuilder;

abstract class AbstractFormTest extends TestCase
{
    /**
     * @var EventDispatcherInterface
     */
    protected $dispatcher;

    /**
     * @var \Symfony2\Component\Form\FormFactoryInterface
     */
    protected $factory;

    /**
     * @var \Symfony2\Component\Form\FormInterface
     */
    protected $form;

    protected function setUp()
    {
        // We need an actual dispatcher to use the deprecated
        // bindRequest() method
        $this->dispatcher = new EventDispatcher();
        $this->factory = $this->getMockBuilder('Symfony2\Component\Form\FormFactoryInterface')->getMock();
        $this->form = $this->createForm();
    }

    protected function tearDown()
    {
        $this->dispatcher = null;
        $this->factory = null;
        $this->form = null;
    }

    /**
     * @return \Symfony2\Component\Form\FormInterface
     */
    abstract protected function createForm();

    /**
     * @param string                   $name
     * @param EventDispatcherInterface $dispatcher
     * @param string                   $dataClass
     * @param array                    $options
     *
     * @return FormBuilder
     */
    protected function getBuilder($name = 'name', EventDispatcherInterface $dispatcher = null, $dataClass = null, array $options = array())
    {
        return new FormBuilder($name, $dataClass, $dispatcher ?: $this->dispatcher, $this->factory, $options);
    }

    /**
     * @param string $name
     *
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockForm($name = 'name')
    {
        $form = $this->getMockBuilder('Symfony2\Component\Form\Test\FormInterface')->getMock();
        $config = $this->getMockBuilder('Symfony2\Component\Form\FormConfigInterface')->getMock();

        $form->expects($this->any())
            ->method('getName')
            ->will($this->returnValue($name));
        $form->expects($this->any())
            ->method('getConfig')
            ->will($this->returnValue($config));

        return $form;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getDataMapper()
    {
        return $this->getMockBuilder('Symfony2\Component\Form\DataMapperInterface')->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getDataTransformer()
    {
        return $this->getMockBuilder('Symfony2\Component\Form\DataTransformerInterface')->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getFormValidator()
    {
        return $this->getMockBuilder('Symfony2\Component\Form\FormValidatorInterface')->getMock();
    }
}
