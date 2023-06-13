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

use Symfony2\Component\Validator\ClassBasedInterface;
use Symfony2\Component\Validator\MetadataInterface;
use Symfony2\Component\Validator\PropertyMetadataContainerInterface;

interface LegacyClassMetadata extends MetadataInterface, PropertyMetadataContainerInterface, ClassBasedInterface
{
}
