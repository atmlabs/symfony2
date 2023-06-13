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

use Symfony2\Component\Form\FormBuilderInterface as BaseFormBuilderInterface;

interface FormBuilderInterface extends \Iterator, BaseFormBuilderInterface
{
}
