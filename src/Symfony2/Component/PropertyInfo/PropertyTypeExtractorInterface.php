<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\PropertyInfo;

/**
 * Type Extractor Interface.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
interface PropertyTypeExtractorInterface
{
    /**
     * Gets types of a property.
     *
     * @param string $class
     * @param string $property
     * @param array  $context
     *
     * @return Type[]|null
     */
    public function getTypes($class, $property, array $context = array());
}
