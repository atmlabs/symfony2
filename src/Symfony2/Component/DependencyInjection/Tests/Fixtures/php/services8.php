<?php

use Symfony2\Component\DependencyInjection\ContainerInterface;
use Symfony2\Component\DependencyInjection\Container;
use Symfony2\Component\DependencyInjection\Exception\InactiveScopeException;
use Symfony2\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony2\Component\DependencyInjection\Exception\LogicException;
use Symfony2\Component\DependencyInjection\Exception\RuntimeException;
use Symfony2\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 * This class has been auto-generated
 * by the Symfony Dependency Injection Component.
 */
class ProjectServiceContainer extends Container
{
    private $parameters;
    private $targetDirs = array();

    public function __construct()
    {
        parent::__construct(new ParameterBag($this->getDefaultParameters()));
    }

    /**
     * Gets the default parameters.
     *
     * @return array An array of the default parameters
     */
    protected function getDefaultParameters()
    {
        return array(
            'foo' => '%baz%',
            'baz' => 'bar',
            'bar' => 'foo is %%foo bar',
            'escape' => '@escapeme',
            'values' => array(
                0 => true,
                1 => false,
                2 => NULL,
                3 => 0,
                4 => 1000.3,
                5 => 'true',
                6 => 'false',
                7 => 'null',
            ),
        );
    }
}
