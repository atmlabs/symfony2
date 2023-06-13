<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Symfony2\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony2\Bundle\FrameworkBundle\Tests\Functional\Bundle\TestBundle\TestBundle;

return array(
    new FrameworkBundle(),
    new TestBundle(),
);
