<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Validator\Mapping\Factory;

use Symfony2\Component\Validator\MetadataFactoryInterface as LegacyMetadataFactoryInterface;

/**
 * Returns {@link \Symfony2\Component\Validator\Mapping\MetadataInterface} instances for values.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
interface MetadataFactoryInterface extends LegacyMetadataFactoryInterface
{
}
