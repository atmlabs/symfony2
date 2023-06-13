<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\SecurityBundle\DependencyInjection\Compiler;

use Symfony2\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony2\Component\DependencyInjection\ContainerBuilder;
use Symfony2\Component\DependencyInjection\Reference;

/**
 * @author Christian Flothmann <christian.flothmann@sensiolabs.de>
 */
class RegisterCsrfTokenClearingLogoutHandlerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('security.logout_listener') || !$container->has('security.csrf.token_storage')) {
            return;
        }

        $csrfTokenStorage = $container->findDefinition('security.csrf.token_storage');
        $csrfTokenStorageClass = $container->getParameterBag()->resolveValue($csrfTokenStorage->getClass());

        if (!is_subclass_of($csrfTokenStorageClass, 'Symfony2\Component\Security\Csrf\TokenStorage\ClearableTokenStorageInterface')) {
            return;
        }

        $container->register('security.logout.handler.csrf_token_clearing', 'Symfony2\Component\Security\Http\Logout\CsrfTokenClearingLogoutHandler')
            ->addArgument(new Reference('security.csrf.token_storage'))
            ->setPublic(false);

        $container->findDefinition('security.logout_listener')->addMethodCall('addHandler', array(new Reference('security.logout.handler.csrf_token_clearing')));
    }
}
