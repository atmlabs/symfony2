<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Validator\Tests\Fixtures;

use Symfony2\Component\Validator\ExecutionContextInterface;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class CallbackClass
{
    public static function callback($object, ExecutionContextInterface $context)
    {
    }
}
