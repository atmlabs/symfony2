<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Routing\Tests\Fixtures;

use Symfony2\Component\Config\Util\XmlUtils;
use Symfony2\Component\Routing\Loader\XmlFileLoader;

/**
 * XmlFileLoader with schema validation turned off.
 */
class CustomXmlFileLoader extends XmlFileLoader
{
    protected function loadFile($file)
    {
        return XmlUtils::loadFile($file, function () { return true; });
    }
}
