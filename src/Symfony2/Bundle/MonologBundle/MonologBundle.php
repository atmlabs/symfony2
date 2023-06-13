<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\MonologBundle;

use Symfony2\Bundle\MonologBundle\DependencyInjection\Compiler\AddSwiftMailerTransportPass;
use Symfony2\Component\HttpKernel\Bundle\Bundle;
use Symfony2\Component\DependencyInjection\ContainerBuilder;
use Symfony2\Bundle\MonologBundle\DependencyInjection\Compiler\LoggerChannelPass;
use Symfony2\Bundle\MonologBundle\DependencyInjection\Compiler\DebugHandlerPass;
use Symfony2\Bundle\MonologBundle\DependencyInjection\Compiler\AddProcessorsPass;

/**
 * Bundle.
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class MonologBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass($channelPass = new LoggerChannelPass());
        $container->addCompilerPass(new DebugHandlerPass($channelPass));
        $container->addCompilerPass(new AddProcessorsPass());
        $container->addCompilerPass(new AddSwiftMailerTransportPass());
    }
}
