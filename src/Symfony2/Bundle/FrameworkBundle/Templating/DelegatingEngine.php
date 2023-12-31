<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\FrameworkBundle\Templating;

use Symfony2\Component\DependencyInjection\ContainerInterface;
use Symfony2\Component\HttpFoundation\Response;
use Symfony2\Component\Templating\DelegatingEngine as BaseDelegatingEngine;

/**
 * DelegatingEngine selects an engine for a given template.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class DelegatingEngine extends BaseDelegatingEngine implements EngineInterface
{
    protected $container;

    public function __construct(ContainerInterface $container, array $engineIds)
    {
        $this->container = $container;
        $this->engines = $engineIds;
    }

    /**
     * {@inheritdoc}
     */
    public function getEngine($name)
    {
        $this->resolveEngines();

        return parent::getEngine($name);
    }

    /**
     * {@inheritdoc}
     */
    public function renderResponse($view, array $parameters = array(), Response $response = null)
    {
        $engine = $this->getEngine($view);

        if ($engine instanceof EngineInterface) {
            return $engine->renderResponse($view, $parameters, $response);
        }

        if (null === $response) {
            $response = new Response();
        }

        $response->setContent($engine->render($view, $parameters));

        return $response;
    }

    /**
     * Resolved engine ids to their real engine instances from the container.
     */
    private function resolveEngines()
    {
        foreach ($this->engines as $i => $engine) {
            if (\is_string($engine)) {
                $this->engines[$i] = $this->container->get($engine);
            }
        }
    }
}
