<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\SecurityBundle\Tests\Functional\Bundle\FormLoginBundle\DependencyInjection;

use Symfony2\Component\DependencyInjection\ContainerBuilder;
use Symfony2\Component\DependencyInjection\Extension\Extension;
use Symfony2\Component\DependencyInjection\Reference;

class FormLoginExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $container
            ->register('localized_form_failure_handler', 'Symfony2\Bundle\SecurityBundle\Tests\Functional\Bundle\FormLoginBundle\Security\LocalizedFormFailureHandler')
            ->addArgument(new Reference('router'))
        ;
    }
}
