<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Config\Tests\Resource;

use Symfony2\Component\Config\Resource\SelfCheckingResourceInterface;

class ResourceStub implements SelfCheckingResourceInterface
{
    private $fresh = true;

    public function setFresh($isFresh)
    {
        $this->fresh = $isFresh;
    }

    public function __toString()
    {
        return 'stub';
    }

    public function isFresh($timestamp)
    {
        return $this->fresh;
    }

    public function getResource()
    {
        return 'stub';
    }
}
