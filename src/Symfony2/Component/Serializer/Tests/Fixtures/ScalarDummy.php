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
use Symfony2\Component\Serializer\Normalizer\NormalizableInterface;
use Symfony2\Component\Serializer\Normalizer\NormalizerInterface;

class ScalarDummy implements NormalizableInterface, DenormalizableInterface
{
    public $foo;
    public $xmlFoo;

    public function normalize(NormalizerInterface $normalizer, $format = null, array $context = array())
    {
        return 'xml' === $format ? $this->xmlFoo : $this->foo;
    }

    public function denormalize(DenormalizerInterface $denormalizer, $data, $format = null, array $context = array())
    {
        if ('xml' === $format) {
            $this->xmlFoo = $data;
        } else {
            $this->foo = $data;
        }
    }
}
