<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\PropertyInfo\Tests\Fixtures;

use Symfony2\Component\PropertyInfo\PropertyAccessExtractorInterface;
use Symfony2\Component\PropertyInfo\PropertyDescriptionExtractorInterface;
use Symfony2\Component\PropertyInfo\PropertyListExtractorInterface;
use Symfony2\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony2\Component\PropertyInfo\Type;

/**
 * Not able to guess anything.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class NullExtractor implements PropertyListExtractorInterface, PropertyDescriptionExtractorInterface, PropertyTypeExtractorInterface, PropertyAccessExtractorInterface
{
    /**
     * {@inheritdoc}
     */
    public function getShortDescription($class, $property, array $context = array())
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getLongDescription($class, $property, array $context = array())
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getTypes($class, $property, array $context = array())
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable($class, $property, array $context = array())
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable($class, $property, array $context = array())
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getProperties($class, array $context = array())
    {
    }
}
