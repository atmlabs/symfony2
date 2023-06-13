<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

return array(
    new Symfony2\Bundle\FrameworkBundle\FrameworkBundle(),
    new Symfony2\Bundle\SecurityBundle\SecurityBundle(),
    new Symfony2\Bundle\TwigBundle\TwigBundle(),
    new Symfony2\Bundle\SecurityBundle\Tests\Functional\Bundle\CsrfFormLoginBundle\CsrfFormLoginBundle(),
);
