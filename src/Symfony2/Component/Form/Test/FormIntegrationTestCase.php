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

use PHPUnit\Framework\TestCase;
use Symfony2\Component\Form\FormFactoryInterface;
use Symfony2\Component\Form\Forms;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
abstract class FormIntegrationTestCase extends TestCase
{
    /**
     * @var FormFactoryInterface
     */
    protected $factory;

    protected function setUp()
    {
        $this->factory = Forms::createFormFactoryBuilder()
            ->addExtensions($this->getExtensions())
            ->getFormFactory();
    }

    protected function getExtensions()
    {
        return array();
    }
}
