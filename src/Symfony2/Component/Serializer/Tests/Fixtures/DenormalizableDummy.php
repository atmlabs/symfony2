<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Serializer\Tests\Fixtures;

use Symfony2\Component\Serializer\Normalizer\DenormalizableInterface;
use Symfony2\Component\Serializer\Normalizer\DenormalizerInterface;

class DenormalizableDummy implements DenormalizableInterface
{
    public function denormalize(DenormalizerInterface $denormalizer, $data, $format = null, array $context = array())
    {
    }
}
