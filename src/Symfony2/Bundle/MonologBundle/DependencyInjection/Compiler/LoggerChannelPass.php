<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\MonologBundle\DependencyInjection\Compiler;

use Symfony2\Component\DependencyInjection\Reference;
use Symfony2\Component\DependencyInjection\ContainerBuilder;
use Symfony2\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony2\Component\DependencyInjection\DefinitionDecorator;
use Symfony2\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * Replaces the default logger by another one with its own channel for tagged services.
 *
 * @author Christophe Coevoet <stof@notk.org>
 */
class LoggerChannelPass implements CompilerPassInterface
{
    protected $channels = array('app');

    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('monolog.logger')) {
            return;
        }

        // create channels necessary for the handlers
        foreach ($container->findTaggedServiceIds('monolog.logger') as $id => $tags) {
            foreach ($tags as $tag) {
                if (empty($tag['channel']) || 'app' === $tag['channel']) {
                    continue;
                }

                $resolvedChannel = $container->getParameterBag()->resolveValue($tag['channel']);

                $definition = $container->getDefinition($id);
                $loggerId = sprintf('monolog.logger.%s', $resolvedChannel);
                $this->createLogger($resolvedChannel, $loggerId, $container);

                foreach ($definition->getArguments() as $index => $argument) {
                    if ($argument instanceof Reference && 'logger' === (string) $argument) {
                        $definition->replaceArgument($index, $this->changeReference($argument, $loggerId));
                    }
                }

                $calls = $definition->getMethodCalls();
                foreach ($calls as $i => $call) {
                    foreach ($call[1] as $index => $argument) {
                        if ($argument instanceof Reference && 'logger' === (string) $argument) {
                            $calls[$i][1][$index] = $this->changeReference($argument, $loggerId);
                        }
                    }
                }
                $definition->setMethodCalls($calls);
            }
        }

        // create additional channels
        foreach ($container->getParameter('monolog.additional_channels') as $chan) {
            $loggerId = sprintf('monolog.logger.%s', $chan);
            $this->createLogger($chan, $loggerId, $container);
        }
        $container->getParameterBag()->remove('monolog.additional_channels');

        // wire handlers to channels
        $handlersToChannels = $container->getParameter('monolog.handlers_to_channels');
        foreach ($handlersToChannels as $handler => $channels) {
            foreach ($this->processChannels($channels) as $channel) {
                try {
                    $logger = $container->getDefinition($channel === 'app' ? 'monolog.logger' : 'monolog.logger.'.$channel);
                } catch (InvalidArgumentException $e) {
                    $msg = 'Monolog configuration error: The logging channel "'.$channel.'" assigned to the "'.substr($handler, 16).'" handler does not exist.';
                    throw new \InvalidArgumentException($msg, 0, $e);
                }
                $logger->addMethodCall('pushHandler', array(new Reference($handler)));
            }
        }
    }

    public function getChannels()
    {
        return $this->channels;
    }

    protected function processChannels($configuration)
    {
        if (null === $configuration) {
            return $this->channels;
        }

        if ('inclusive' === $configuration['type']) {
            return $configuration['elements'] ?: $this->channels;
        }

        return array_diff($this->channels, $configuration['elements']);
    }

    protected function createLogger($channel, $loggerId, ContainerBuilder $container)
    {
        if (!in_array($channel, $this->channels)) {
            $logger = new DefinitionDecorator('monolog.logger_prototype');
            $logger->replaceArgument(0, $channel);
            $container->setDefinition($loggerId, $logger);
            $this->channels[] = $channel;
        }
    }

    /**
     * Creates a copy of a reference and alters the service ID.
     *
     * @param Reference $reference
     * @param string    $serviceId
     *
     * @return Reference
     */
    private function changeReference(Reference $reference, $serviceId)
    {
        if (method_exists($reference, 'isStrict')) {
            // Stay compatible with Symfony 2
            return new Reference($serviceId, $reference->getInvalidBehavior(), $reference->isStrict(false));
        }

        return new Reference($serviceId, $reference->getInvalidBehavior());
    }
}
