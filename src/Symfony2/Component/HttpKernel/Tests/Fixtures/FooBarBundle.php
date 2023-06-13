<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\HttpKernel\Tests\Fixtures;

use Symfony2\Component\HttpKernel\Bundle\Bundle;

class FooBarBundle extends Bundle
{
    // We need a full namespaced bundle instance to test isClassInActiveBundle
}
