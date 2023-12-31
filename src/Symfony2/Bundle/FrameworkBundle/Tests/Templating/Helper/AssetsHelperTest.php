<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\FrameworkBundle\Tests\Templating\Helper;

use PHPUnit\Framework\TestCase;
use Symfony2\Bundle\FrameworkBundle\Templating\Helper\AssetsHelper;
use Symfony2\Component\Asset\Package;
use Symfony2\Component\Asset\Packages;
use Symfony2\Component\Asset\PathPackage;
use Symfony2\Component\Asset\VersionStrategy\StaticVersionStrategy;

class AssetsHelperTest extends TestCase
{
    /**
     * @group legacy
     */
    public function testLegacyGetUrl()
    {
        $versionStrategy = new StaticVersionStrategy('22', '%s?version=%s');
        $package = new Package($versionStrategy);
        $imagePackage = new PathPackage('images', $versionStrategy);
        $packages = new Packages($package, array('images' => $imagePackage));
        $helper = new AssetsHelper($packages);

        $this->assertEquals('me.png?version=42', $helper->getUrl('me.png', null, '42'));
        $this->assertEquals('/images/me.png?version=42', $helper->getUrl('me.png', 'images', '42'));
    }

    /**
     * @group legacy
     */
    public function testLegacyGetVersion()
    {
        $package = new Package(new StaticVersionStrategy('22'));
        $imagePackage = new Package(new StaticVersionStrategy('42'));
        $packages = new Packages($package, array('images' => $imagePackage));
        $helper = new AssetsHelper($packages);

        $this->assertEquals('22', $helper->getVersion());
        $this->assertEquals('22', $helper->getVersion('/foo'));
        $this->assertEquals('42', $helper->getVersion('images'));
        $this->assertEquals('42', $helper->getVersion('/foo', 'images'));
    }
}
