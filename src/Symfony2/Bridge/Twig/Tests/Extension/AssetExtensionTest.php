<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bridge\Twig\Tests\Extension;

use PHPUnit\Framework\TestCase;
use Symfony2\Bridge\Twig\Extension\AssetExtension;
use Symfony2\Component\Asset\Package;
use Symfony2\Component\Asset\Packages;
use Symfony2\Component\Asset\PathPackage;
use Symfony2\Component\Asset\VersionStrategy\EmptyVersionStrategy;
use Symfony2\Component\Asset\VersionStrategy\StaticVersionStrategy;

class AssetExtensionTest extends TestCase
{
    /**
     * @group legacy
     */
    public function testLegacyGetAssetUrl()
    {
        $extension = $this->createExtension(new Package(new StaticVersionStrategy('22', '%s?version=%s')));

        $this->assertEquals('me.png?version=42', $extension->getAssetUrl('me.png', null, false, '42'));
        $this->assertEquals('http://localhost/me.png?version=22', $extension->getAssetUrl('me.png', null, true));
        $this->assertEquals('http://localhost/me.png?version=42', $extension->getAssetUrl('me.png', null, true, '42'));
    }

    /**
     * @group legacy
     */
    public function testGetAssetUrlWithPackageSubClass()
    {
        $extension = $this->createExtension(new PathPackage('foo', new StaticVersionStrategy('22', '%s?version=%s')));

        $this->assertEquals('/foo/me.png?version=42', $extension->getAssetUrl('me.png', null, false, 42));
    }

    /**
     * @group legacy
     */
    public function testGetAssetUrlWithEmptyVersionStrategy()
    {
        $extension = $this->createExtension(new PathPackage('foo', new EmptyVersionStrategy()));

        $this->assertEquals('/foo/me.png?42', $extension->getAssetUrl('me.png', null, false, 42));
    }

    private function createExtension(Package $package)
    {
        $foundationExtension = $this->getMockBuilder('Symfony2\Bridge\Twig\Extension\HttpFoundationExtension')->disableOriginalConstructor()->getMock();
        $foundationExtension
            ->expects($this->any())
            ->method('generateAbsoluteUrl')
            ->will($this->returnCallback(function ($arg) { return 'http://localhost/'.$arg; }))
        ;

        return new AssetExtension(new Packages($package), $foundationExtension);
    }
}
