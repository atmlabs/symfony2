<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\FrameworkBundle\Tests\Functional\Bundle\TestBundle\Controller;

use Symfony2\Component\DependencyInjection\ContainerAware;
use Symfony2\Component\HttpFoundation\Response;

class ProfilerController extends ContainerAware
{
    public function indexAction()
    {
        return new Response('Hello');
    }
}
