<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Form\Test;

use Symfony2\Component\EventDispatcher\EventDispatcher;
use Symfony2\Component\Form\FormBuilder;

abstract class TypeTestCase extends FormIntegrationTestCase
{
    /**
     * @var FormBuilder
     */
    protected $builder;

    /**
     * @var EventDispatcher
     */
    protected $dispatcher;

    protected function setUp()
    {
        parent::setUp();

        $this->dispatcher = $this->getMockBuilder('Symfony2\Component\EventDispatcher\EventDispatcherInterface')->getMock();
        $this->builder = new FormBuilder(null, null, $this->dispatcher, $this->factory);
    }
}
