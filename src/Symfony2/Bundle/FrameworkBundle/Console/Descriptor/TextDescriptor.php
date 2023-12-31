<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\FrameworkBundle\Console\Descriptor;

use Symfony2\Component\Console\Helper\Table;
use Symfony2\Component\Console\Style\SymfonyStyle;
use Symfony2\Component\DependencyInjection\Alias;
use Symfony2\Component\DependencyInjection\ContainerBuilder;
use Symfony2\Component\DependencyInjection\Definition;
use Symfony2\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony2\Component\DependencyInjection\Reference;
use Symfony2\Component\EventDispatcher\EventDispatcherInterface;
use Symfony2\Component\Routing\Route;
use Symfony2\Component\Routing\RouteCollection;

/**
 * @author Jean-François Simon <jeanfrancois.simon@sensiolabs.com>
 *
 * @internal
 */
class TextDescriptor extends Descriptor
{
    /**
     * {@inheritdoc}
     */
    protected function describeRouteCollection(RouteCollection $routes, array $options = array())
    {
        $showControllers = isset($options['show_controllers']) && $options['show_controllers'];

        $tableHeaders = array('Name', 'Method', 'Scheme', 'Host', 'Path');
        if ($showControllers) {
            $tableHeaders[] = 'Controller';
        }

        $tableRows = array();
        foreach ($routes->all() as $name => $route) {
            $row = array(
                $name,
                $route->getMethods() ? implode('|', $route->getMethods()) : 'ANY',
                $route->getSchemes() ? implode('|', $route->getSchemes()) : 'ANY',
                '' !== $route->getHost() ? $route->getHost() : 'ANY',
                $route->getPath(),
            );

            if ($showControllers) {
                $controller = $route->getDefault('_controller');
                if ($controller instanceof \Closure) {
                    $controller = 'Closure';
                } elseif (\is_object($controller)) {
                    $controller = \get_class($controller);
                }
                $row[] = $controller;
            }

            $tableRows[] = $row;
        }

        if (isset($options['output'])) {
            $options['output']->table($tableHeaders, $tableRows);
        } else {
            $table = new Table($this->getOutput());
            $table->setHeaders($tableHeaders)->setRows($tableRows);
            $table->render();
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function describeRoute(Route $route, array $options = array())
    {
        $requirements = $route->getRequirements();
        unset($requirements['_scheme'], $requirements['_method']);

        $tableHeaders = array('Property', 'Value');
        $tableRows = array(
            array('Route Name', isset($options['name']) ? $options['name'] : ''),
            array('Path', $route->getPath()),
            array('Path Regex', $route->compile()->getRegex()),
            array('Host', ('' !== $route->getHost() ? $route->getHost() : 'ANY')),
            array('Host Regex', ('' !== $route->getHost() ? $route->compile()->getHostRegex() : '')),
            array('Scheme', ($route->getSchemes() ? implode('|', $route->getSchemes()) : 'ANY')),
            array('Method', ($route->getMethods() ? implode('|', $route->getMethods()) : 'ANY')),
            array('Requirements', ($requirements ? $this->formatRouterConfig($requirements) : 'NO CUSTOM')),
            array('Class', \get_class($route)),
            array('Defaults', $this->formatRouterConfig($route->getDefaults())),
            array('Options', $this->formatRouterConfig($route->getOptions())),
        );

        $table = new Table($this->getOutput());
        $table->setHeaders($tableHeaders)->setRows($tableRows);
        $table->render();
    }

    /**
     * {@inheritdoc}
     */
    protected function describeContainerParameters(ParameterBag $parameters, array $options = array())
    {
        $tableHeaders = array('Parameter', 'Value');

        $tableRows = array();
        foreach ($this->sortParameters($parameters) as $parameter => $value) {
            $tableRows[] = array($parameter, $this->formatParameter($value));
        }

        $options['output']->title('Symfony Container Parameters');
        $options['output']->table($tableHeaders, $tableRows);
    }

    /**
     * {@inheritdoc}
     */
    protected function describeContainerTags(ContainerBuilder $builder, array $options = array())
    {
        $showPrivate = isset($options['show_private']) && $options['show_private'];

        if ($showPrivate) {
            $options['output']->title('Symfony Container Public and Private Tags');
        } else {
            $options['output']->title('Symfony Container Public Tags');
        }

        foreach ($this->findDefinitionsByTag($builder, $showPrivate) as $tag => $definitions) {
            $options['output']->section(sprintf('"%s" tag', $tag));
            $options['output']->listing(array_keys($definitions));
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function describeContainerService($service, array $options = array())
    {
        if (!isset($options['id'])) {
            throw new \InvalidArgumentException('An "id" option must be provided.');
        }

        if ($service instanceof Alias) {
            $this->describeContainerAlias($service, $options);
        } elseif ($service instanceof Definition) {
            $this->describeContainerDefinition($service, $options);
        } else {
            $options['output']->title(sprintf('Information for Service "<info>%s</info>"', $options['id']));
            $options['output']->table(
                array('Service ID', 'Class'),
                array(
                    array(isset($options['id']) ? $options['id'] : '-', \get_class($service)),
                )
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function describeContainerServices(ContainerBuilder $builder, array $options = array())
    {
        $showPrivate = isset($options['show_private']) && $options['show_private'];
        $showTag = isset($options['tag']) ? $options['tag'] : null;

        if ($showPrivate) {
            $title = 'Symfony Container Public and Private Services';
        } else {
            $title = 'Symfony Container Public Services';
        }

        if ($showTag) {
            $title .= sprintf(' Tagged with "%s" Tag', $options['tag']);
        }

        $options['output']->title($title);

        $serviceIds = isset($options['tag']) && $options['tag'] ? array_keys($builder->findTaggedServiceIds($options['tag'])) : $builder->getServiceIds();
        $maxTags = array();

        foreach ($serviceIds as $key => $serviceId) {
            $definition = $this->resolveServiceDefinition($builder, $serviceId);
            if ($definition instanceof Definition) {
                // filter out private services unless shown explicitly
                if (!$showPrivate && !$definition->isPublic()) {
                    unset($serviceIds[$key]);
                    continue;
                }
                if ($showTag) {
                    $tags = $definition->getTag($showTag);
                    foreach ($tags as $tag) {
                        foreach ($tag as $key => $value) {
                            if (!isset($maxTags[$key])) {
                                $maxTags[$key] = \strlen($key);
                            }
                            if (\strlen($value) > $maxTags[$key]) {
                                $maxTags[$key] = \strlen($value);
                            }
                        }
                    }
                }
            }
        }

        $tagsCount = \count($maxTags);
        $tagsNames = array_keys($maxTags);

        $tableHeaders = array_merge(array('Service ID'), $tagsNames, array('Class name'));
        $tableRows = array();
        foreach ($this->sortServiceIds($serviceIds) as $serviceId) {
            $definition = $this->resolveServiceDefinition($builder, $serviceId);
            if ($definition instanceof Definition) {
                if ($showTag) {
                    foreach ($definition->getTag($showTag) as $key => $tag) {
                        $tagValues = array();
                        foreach ($tagsNames as $tagName) {
                            $tagValues[] = isset($tag[$tagName]) ? $tag[$tagName] : '';
                        }
                        if (0 === $key) {
                            $tableRows[] = array_merge(array($serviceId), $tagValues, array($definition->getClass()));
                        } else {
                            $tableRows[] = array_merge(array('  "'), $tagValues, array(''));
                        }
                    }
                } else {
                    $tableRows[] = array($serviceId, $definition->getClass());
                }
            } elseif ($definition instanceof Alias) {
                $alias = $definition;
                $tableRows[] = array_merge(array($serviceId, sprintf('alias for "%s"', $alias)), $tagsCount ? array_fill(0, $tagsCount, '') : array());
            } else {
                $tableRows[] = array_merge(array($serviceId, \get_class($definition)), $tagsCount ? array_fill(0, $tagsCount, '') : array());
            }
        }

        $options['output']->table($tableHeaders, $tableRows);
    }

    /**
     * {@inheritdoc}
     */
    protected function describeContainerDefinition(Definition $definition, array $options = array())
    {
        if (isset($options['id'])) {
            $options['output']->title(sprintf('Information for Service "<info>%s</info>"', $options['id']));
        }

        $tableHeaders = array('Option', 'Value');

        $tableRows[] = array('Service ID', isset($options['id']) ? $options['id'] : '-');
        $tableRows[] = array('Class', $definition->getClass() ?: '-');

        if ($tags = $definition->getTags()) {
            $tagInformation = '';
            foreach ($tags as $tagName => $tagData) {
                foreach ($tagData as $tagParameters) {
                    $parameters = array_map(function ($key, $value) {
                        return sprintf('<info>%s</info>: %s', $key, $value);
                    }, array_keys($tagParameters), array_values($tagParameters));
                    $parameters = implode(', ', $parameters);

                    if ('' === $parameters) {
                        $tagInformation .= sprintf('%s', $tagName);
                    } else {
                        $tagInformation .= sprintf('%s (%s)', $tagName, $parameters);
                    }
                }
            }
        } else {
            $tagInformation = '-';
        }
        $tableRows[] = array('Tags', $tagInformation);

        $tableRows[] = array('Scope', $definition->getScope(false));
        $tableRows[] = array('Public', $definition->isPublic() ? 'yes' : 'no');
        $tableRows[] = array('Synthetic', $definition->isSynthetic() ? 'yes' : 'no');
        $tableRows[] = array('Lazy', $definition->isLazy() ? 'yes' : 'no');
        $tableRows[] = array('Synchronized', $definition->isSynchronized(false) ? 'yes' : 'no');
        $tableRows[] = array('Abstract', $definition->isAbstract() ? 'yes' : 'no');
        $tableRows[] = array('Autowired', $definition->isAutowired() ? 'yes' : 'no');

        $autowiringTypes = $definition->getAutowiringTypes();
        $tableRows[] = array('Autowiring Types', $autowiringTypes ? implode(', ', $autowiringTypes) : '-');

        if ($definition->getFile()) {
            $tableRows[] = array('Required File', $definition->getFile() ? $definition->getFile() : '-');
        }

        if ($definition->getFactoryClass(false)) {
            $tableRows[] = array('Factory Class', $definition->getFactoryClass(false));
        }

        if ($definition->getFactoryService(false)) {
            $tableRows[] = array('Factory Service', $definition->getFactoryService(false));
        }

        if ($definition->getFactoryMethod(false)) {
            $tableRows[] = array('Factory Method', $definition->getFactoryMethod(false));
        }

        if ($factory = $definition->getFactory()) {
            if (\is_array($factory)) {
                if ($factory[0] instanceof Reference) {
                    $tableRows[] = array('Factory Service', $factory[0]);
                } elseif ($factory[0] instanceof Definition) {
                    throw new \InvalidArgumentException('Factory is not describable.');
                } else {
                    $tableRows[] = array('Factory Class', $factory[0]);
                }
                $tableRows[] = array('Factory Method', $factory[1]);
            } else {
                $tableRows[] = array('Factory Function', $factory);
            }
        }

        $options['output']->table($tableHeaders, $tableRows);
    }

    /**
     * {@inheritdoc}
     */
    protected function describeContainerAlias(Alias $alias, array $options = array())
    {
        $options['output']->comment(sprintf('This service is an alias for the service <info>%s</info>', (string) $alias));
    }

    /**
     * {@inheritdoc}
     */
    protected function describeContainerParameter($parameter, array $options = array())
    {
        $options['output']->table(
            array('Parameter', 'Value'),
            array(
                array($options['parameter'], $this->formatParameter($parameter),
            ),
        ));
    }

    /**
     * {@inheritdoc}
     */
    protected function describeEventDispatcherListeners(EventDispatcherInterface $eventDispatcher, array $options = array())
    {
        $event = array_key_exists('event', $options) ? $options['event'] : null;

        if (null !== $event) {
            $title = sprintf('Registered Listeners for "%s" Event', $event);
        } else {
            $title = 'Registered Listeners Grouped by Event';
        }

        $options['output']->title($title);

        $registeredListeners = $eventDispatcher->getListeners($event);
        if (null !== $event) {
            $this->renderEventListenerTable($eventDispatcher, $event, $registeredListeners, $options['output']);
        } else {
            ksort($registeredListeners);
            foreach ($registeredListeners as $eventListened => $eventListeners) {
                $options['output']->section(sprintf('"%s" event', $eventListened));
                $this->renderEventListenerTable($eventDispatcher, $eventListened, $eventListeners, $options['output']);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function describeCallable($callable, array $options = array())
    {
        $this->writeText($this->formatCallable($callable), $options);
    }

    private function renderEventListenerTable(EventDispatcherInterface $eventDispatcher, $event, array $eventListeners, SymfonyStyle $io)
    {
        $tableHeaders = array('Order', 'Callable', 'Priority');
        $tableRows = array();

        $order = 1;
        foreach ($eventListeners as $order => $listener) {
            $tableRows[] = array(sprintf('#%d', $order + 1), $this->formatCallable($listener), $eventDispatcher->getListenerPriority($event, $listener));
        }

        $io->table($tableHeaders, $tableRows);
    }

    /**
     * @param array $config
     *
     * @return string
     */
    private function formatRouterConfig(array $config)
    {
        if (empty($config)) {
            return 'NONE';
        }

        ksort($config);

        $configAsString = '';
        foreach ($config as $key => $value) {
            $configAsString .= sprintf("\n%s: %s", $key, $this->formatValue($value));
        }

        return trim($configAsString);
    }

    /**
     * @param callable $callable
     *
     * @return string
     */
    private function formatCallable($callable)
    {
        if (\is_array($callable)) {
            if (\is_object($callable[0])) {
                return sprintf('%s::%s()', \get_class($callable[0]), $callable[1]);
            }

            return sprintf('%s::%s()', $callable[0], $callable[1]);
        }

        if (\is_string($callable)) {
            return sprintf('%s()', $callable);
        }

        if ($callable instanceof \Closure) {
            return '\Closure()';
        }

        if (method_exists($callable, '__invoke')) {
            return sprintf('%s::__invoke()', \get_class($callable));
        }

        throw new \InvalidArgumentException('Callable is not describable.');
    }

    /**
     * @param string $content
     * @param array  $options
     */
    private function writeText($content, array $options = array())
    {
        $this->write(
            isset($options['raw_text']) && $options['raw_text'] ? strip_tags($content) : $content,
            isset($options['raw_output']) ? !$options['raw_output'] : true
        );
    }
}
