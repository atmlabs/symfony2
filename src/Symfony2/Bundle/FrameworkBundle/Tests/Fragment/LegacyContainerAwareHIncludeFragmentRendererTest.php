<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\FrameworkBundle\Tests\Fragment;

use Symfony2\Bundle\FrameworkBundle\Fragment\ContainerAwareHIncludeFragmentRenderer;
use Symfony2\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony2\Component\HttpFoundation\Request;

/**
 * @group legacy
 */
class LegacyContainerAwareHIncludeFragmentRendererTest extends TestCase
{
    public function testRender()
    {
        $container = $this->getMockBuilder('Symfony2\Component\DependencyInjection\ContainerInterface')->getMock();
        $container->expects($this->once())
            ->method('get')
            ->will($this->returnValue($this->getMockBuilder('Twig\Environment')->disableOriginalConstructor()->getMock()))
        ;
        $renderer = new ContainerAwareHIncludeFragmentRenderer($container);
        $renderer->render('/', Request::create('/'));
    }
}
