<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\SecurityBundle;

use Symfony2\Bundle\SecurityBundle\DependencyInjection\Compiler\AddSecurityVotersPass;
use Symfony2\Bundle\SecurityBundle\DependencyInjection\Compiler\AddSessionDomainConstraintPass;
use Symfony2\Bundle\SecurityBundle\DependencyInjection\Compiler\RegisterCsrfTokenClearingLogoutHandlerPass;
use Symfony2\Bundle\SecurityBundle\DependencyInjection\Security\Factory\FormLoginFactory;
use Symfony2\Bundle\SecurityBundle\DependencyInjection\Security\Factory\FormLoginLdapFactory;
use Symfony2\Bundle\SecurityBundle\DependencyInjection\Security\Factory\GuardAuthenticationFactory;
use Symfony2\Bundle\SecurityBundle\DependencyInjection\Security\Factory\HttpBasicFactory;
use Symfony2\Bundle\SecurityBundle\DependencyInjection\Security\Factory\HttpBasicLdapFactory;
use Symfony2\Bundle\SecurityBundle\DependencyInjection\Security\Factory\HttpDigestFactory;
use Symfony2\Bundle\SecurityBundle\DependencyInjection\Security\Factory\RememberMeFactory;
use Symfony2\Bundle\SecurityBundle\DependencyInjection\Security\Factory\RemoteUserFactory;
use Symfony2\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SimpleFormFactory;
use Symfony2\Bundle\SecurityBundle\DependencyInjection\Security\Factory\SimplePreAuthenticationFactory;
use Symfony2\Bundle\SecurityBundle\DependencyInjection\Security\Factory\X509Factory;
use Symfony2\Bundle\SecurityBundle\DependencyInjection\Security\UserProvider\InMemoryFactory;
use Symfony2\Bundle\SecurityBundle\DependencyInjection\Security\UserProvider\LdapFactory;
use Symfony2\Component\DependencyInjection\Compiler\PassConfig;
use Symfony2\Component\DependencyInjection\ContainerBuilder;
use Symfony2\Component\HttpKernel\Bundle\Bundle;

/**
 * Bundle.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SecurityBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $extension = $container->getExtension('security');
        $extension->addSecurityListenerFactory(new FormLoginFactory());
        $extension->addSecurityListenerFactory(new FormLoginLdapFactory());
        $extension->addSecurityListenerFactory(new HttpBasicFactory());
        $extension->addSecurityListenerFactory(new HttpBasicLdapFactory());
        $extension->addSecurityListenerFactory(new HttpDigestFactory());
        $extension->addSecurityListenerFactory(new RememberMeFactory());
        $extension->addSecurityListenerFactory(new X509Factory());
        $extension->addSecurityListenerFactory(new RemoteUserFactory());
        $extension->addSecurityListenerFactory(new SimplePreAuthenticationFactory());
        $extension->addSecurityListenerFactory(new SimpleFormFactory());
        $extension->addSecurityListenerFactory(new GuardAuthenticationFactory());

        $extension->addUserProviderFactory(new InMemoryFactory());
        $extension->addUserProviderFactory(new LdapFactory());
        $container->addCompilerPass(new AddSecurityVotersPass());
        $container->addCompilerPass(new AddSessionDomainConstraintPass(), PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new RegisterCsrfTokenClearingLogoutHandlerPass());
    }
}
