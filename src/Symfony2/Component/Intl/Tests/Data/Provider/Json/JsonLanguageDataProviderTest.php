<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Intl\Tests\Data\Provider\Json;

use Symfony2\Component\Intl\Data\Bundle\Reader\BundleReaderInterface;
use Symfony2\Component\Intl\Data\Bundle\Reader\JsonBundleReader;
use Symfony2\Component\Intl\Intl;
use Symfony2\Component\Intl\Tests\Data\Provider\AbstractLanguageDataProviderTest;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @group intl-data
 */
class JsonLanguageDataProviderTest extends AbstractLanguageDataProviderTest
{
    protected function getDataDirectory()
    {
        return Intl::getDataDirectory();
    }

    /**
     * @return BundleReaderInterface
     */
    protected function createBundleReader()
    {
        return new JsonBundleReader();
    }
}
