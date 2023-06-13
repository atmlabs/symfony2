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
        parent::__construct();
    }
}
