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

use Symfony2\Component\Validator\Mapping\ClassMetadata;

class FakeClassMetadata extends ClassMetadata
{
    public function addCustomPropertyMetadata($propertyName, $metadata)
    {
        if (!isset($this->members[$propertyName])) {
            $this->members[$propertyName] = array();
        }

        $this->members[$propertyName][] = $metadata;
    }
}
