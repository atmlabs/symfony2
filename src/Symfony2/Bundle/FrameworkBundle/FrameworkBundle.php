<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\FrameworkBundle;

use Symfony2\Bundle\FrameworkBundle\DependencyInjection\Compiler\AddCacheClearerPass;
use Symfony2\Bundle\FrameworkBundle\DependencyInjection\Compiler\AddCacheWarmerPass;
use Symfony2\Bundle\FrameworkBundle\DependencyInjection\Compiler\AddConsoleCommandPass;
use Symfony2\Bundle\FrameworkBundle\DependencyInjection\Compiler\AddConstraintValidatorsPass;
use Symfony2\Bundle\FrameworkBundle\DependencyInjection\Compiler\AddExpressionLanguageProvidersPass;
use Symfony2\Bundle\FrameworkBundle\DependencyInjection\Compiler\AddValidatorInitializersPass;
use Symfony2\Bundle\FrameworkBundle\DependencyInjection\Compiler\CompilerDebugDumpPass;
use Symfony2\Bundle\FrameworkBundle\DependencyInjection\Compiler\ConfigCachePass;
use Symfony2\Bundle\FrameworkBundle\DependencyInjection\Compiler\ContainerBuilderDebugDumpPass;
use Symfony2\Bundle\FrameworkBundle\DependencyInjection\Compiler\DataCollectorTranslatorPass;
use Symfony2\Bundle\FrameworkBundle\DependencyInjection\Compiler\FormPass;
use Symfony2\Bundle\FrameworkBundle\DependencyInjection\Compiler\LoggingTranslatorPass;
use Symfony2\Bundle\FrameworkBundle\DependencyInjection\Compiler\ProfilerPass;
use Symfony2\Bundle\FrameworkBundle\DependencyInjection\Compiler\PropertyInfoPass;
use Symfony2\Bundle\FrameworkBundle\DependencyInjection\Compiler\RoutingResolverPass;
use Symfony2\Bundle\FrameworkBundle\DependencyInjection\Compiler\SerializerPass;
use Symfony2\Bundle\FrameworkBundle\DependencyInjection\Compiler\TemplatingPass;
use Symfony2\Bundle\FrameworkBundle\DependencyInjection\Compiler\TranslationDumperPass;
use Symfony2\Bundle\FrameworkBundle\DependencyInjection\Compiler\TranslationExtractorPass;
use Symfony2\Bundle\FrameworkBundle\DependencyInjection\Compiler\TranslatorPass;
use Symfony2\Bundle\FrameworkBundle\DependencyInjection\Compiler\UnusedTagsPass;
use Symfony2\Component\Debug\ErrorHandler;
use Symfony2\Component\DependencyInjection\Compiler\PassConfig;
use Symfony2\Component\DependencyInjection\ContainerBuilder;
use Symfony2\Component\DependencyInjection\Scope;
use Symfony2\Component\EventDispatcher\DependencyInjection\RegisterListenersPass;
use Symfony2\Component\HttpFoundation\Request;
use Symfony2\Component\HttpKernel\Bundle\Bundle;
use Symfony2\Component\HttpKernel\DependencyInjection\FragmentRendererPass;

/**
 * Bundle.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class FrameworkBundle extends Bundle
{
    public function boot()
    {
        ErrorHandler::register(null, false)->throwAt($this->container->getParameter('debug.error_handler.throw_at'), true);

        if ($trustedProxies = $this->container->getParameter('kernel.trusted_proxies')) {
            Request::setTrustedProxies($trustedProxies);
        }

        if ($this->container->getParameter('kernel.http_method_override')) {
            Request::enableHttpMethodParameterOverride();
        }

        if ($trustedHosts = $this->container->getParameter('kernel.trusted_hosts')) {
            Request::setTrustedHosts($trustedHosts);
        }
    }

    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        // we need to add the request scope as early as possible so that
        // the compilation can find scope widening issues
        $container->addScope(new Scope('request'));

        $container->addCompilerPass(new RoutingResolverPass());
        $container->addCompilerPass(new ProfilerPass());
        // must be registered before removing private services as some might be listeners/subscribers
        // but as late as possible to get resolved parameters
        $container->addCompilerPass(new RegisterListenersPass(), PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new TemplatingPass());
        $container->addCompilerPass(new AddConstraintValidatorsPass(), PassConfig::TYPE_BEFORE_REMOVING);
        $container->addCompilerPass(new AddValidatorInitializersPass());
        $container->addCompilerPass(new AddConsoleCommandPass());
        $container->addCompilerPass(new FormPass());
        $container->addCompilerPass(new TranslatorPass());
        $container->addCompilerPass(new LoggingTranslatorPass());
        $container->addCompilerPass(new AddCacheWarmerPass());
        $container->addCompilerPass(new AddCacheClearerPass());
        $container->addCompilerPass(new AddExpressionLanguageProvidersPass());
        $container->addCompilerPass(new TranslationExtractorPass());
        $container->addCompilerPass(new TranslationDumperPass());
        $container->addCompilerPass(new FragmentRendererPass(), PassConfig::TYPE_AFTER_REMOVING);
        $container->addCompilerPass(new SerializerPass());
        $container->addCompilerPass(new PropertyInfoPass());
        $container->addCompilerPass(new DataCollectorTranslatorPass());

        if ($container->getParameter('kernel.debug')) {
            $container->addCompilerPass(new UnusedTagsPass(), PassConfig::TYPE_AFTER_REMOVING);
            $container->addCompilerPass(new ContainerBuilderDebugDumpPass(), PassConfig::TYPE_AFTER_REMOVING);
            $container->addCompilerPass(new CompilerDebugDumpPass(), PassConfig::TYPE_AFTER_REMOVING);
            $container->addCompilerPass(new ConfigCachePass());
        }
    }
}
