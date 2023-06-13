<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\FrameworkBundle\Console\Helper;

use Symfony2\Bundle\FrameworkBundle\Console\Descriptor\JsonDescriptor;
use Symfony2\Bundle\FrameworkBundle\Console\Descriptor\MarkdownDescriptor;
use Symfony2\Bundle\FrameworkBundle\Console\Descriptor\TextDescriptor;
use Symfony2\Bundle\FrameworkBundle\Console\Descriptor\XmlDescriptor;
use Symfony2\Component\Console\Helper\DescriptorHelper as BaseDescriptorHelper;

/**
 * @author Jean-François Simon <jeanfrancois.simon@sensiolabs.com>
 *
 * @internal
 */
class DescriptorHelper extends BaseDescriptorHelper
{
    public function __construct()
    {
        $this
            ->register('txt', new TextDescriptor())
            ->register('xml', new XmlDescriptor())
            ->register('json', new JsonDescriptor())
            ->register('md', new MarkdownDescriptor())
        ;
    }
}
