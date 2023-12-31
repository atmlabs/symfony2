<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Security\Http\Tests;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\Security\Http\AccessMap;

class AccessMapTest extends TestCase
{
    public function testReturnsFirstMatchedPattern()
    {
        $request = $this->getMockBuilder('Symfony2\Component\HttpFoundation\Request')->getMock();
        $requestMatcher1 = $this->getRequestMatcher($request, false);
        $requestMatcher2 = $this->getRequestMatcher($request, true);

        $map = new AccessMap();
        $map->add($requestMatcher1, array('ROLE_ADMIN'), 'http');
        $map->add($requestMatcher2, array('ROLE_USER'), 'https');

        $this->assertSame(array(array('ROLE_USER'), 'https'), $map->getPatterns($request));
    }

    public function testReturnsEmptyPatternIfNoneMatched()
    {
        $request = $this->getMockBuilder('Symfony2\Component\HttpFoundation\Request')->getMock();
        $requestMatcher = $this->getRequestMatcher($request, false);

        $map = new AccessMap();
        $map->add($requestMatcher, array('ROLE_USER'), 'https');

        $this->assertSame(array(null, null), $map->getPatterns($request));
    }

    private function getRequestMatcher($request, $matches)
    {
        $requestMatcher = $this->getMockBuilder('Symfony2\Component\HttpFoundation\RequestMatcherInterface')->getMock();
        $requestMatcher->expects($this->once())
            ->method('matches')->with($request)
            ->will($this->returnValue($matches));

        return $requestMatcher;
    }
}
