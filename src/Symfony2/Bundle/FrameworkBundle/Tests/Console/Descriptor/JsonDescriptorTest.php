<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\FrameworkBundle\Tests\Console\Descriptor;

use Symfony2\Bundle\FrameworkBundle\Console\Descriptor\JsonDescriptor;

/**
 * @requires PHP 5.4
 */
class JsonDescriptorTest extends AbstractDescriptorTest
{
    protected function getDescriptor()
    {
        return new JsonDescriptor();
    }

    protected function getFormat()
    {
        return 'json';
    }
}
