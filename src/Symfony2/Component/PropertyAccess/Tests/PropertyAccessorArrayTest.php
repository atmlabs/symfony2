<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\PropertyAccess\Tests;

class PropertyAccessorArrayTest extends PropertyAccessorCollectionTest
{
    protected function getContainer(array $array)
    {
        return $array;
    }
}
