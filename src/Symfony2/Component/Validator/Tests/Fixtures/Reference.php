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

class Reference
{
    public $value;

    private $privateValue;

    public function setPrivateValue($privateValue)
    {
        $this->privateValue = $privateValue;
    }

    public function getPrivateValue()
    {
        return $this->privateValue;
    }
}
