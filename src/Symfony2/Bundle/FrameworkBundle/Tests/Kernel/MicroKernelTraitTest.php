<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\FrameworkBundle\Tests\Kernel;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\HttpFoundation\Request;

class MicroKernelTraitTest extends TestCase
{
    /**
     * @requires PHP 5.4
     */
    public function test()
    {
        $kernel = new ConcreteMicroKernel('test', true);
        $kernel->boot();

        $request = Request::create('/');
        $response = $kernel->handle($request);

        $this->assertEquals('halloween', $response->getContent());
        $this->assertEquals('Have a great day!', $kernel->getContainer()->getParameter('halloween'));
        $this->assertInstanceOf('stdClass', $kernel->getContainer()->get('halloween'));
    }
}
