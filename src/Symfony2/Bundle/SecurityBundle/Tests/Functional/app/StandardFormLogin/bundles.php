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
use Symfony2\Bundle\SecurityBundle\SecurityBundle;
use Symfony2\Bundle\SecurityBundle\Tests\Functional\Bundle\FormLoginBundle\FormLoginBundle;
use Symfony2\Bundle\TwigBundle\TwigBundle;

return array(
    new FrameworkBundle(),
    new SecurityBundle(),
    new TwigBundle(),
    new FormLoginBundle(),
);
