<?php

use Symfony2\Component\DependencyInjection\ContainerInterface;
use Symfony2\Component\DependencyInjection\Container;
use Symfony2\Component\DependencyInjection\Exception\InactiveScopeException;
use Symfony2\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony2\Component\DependencyInjection\Exception\LogicException;
use Symfony2\Component\DependencyInjection\Exception\RuntimeException;
use Symfony2\Component\DependencyInjection\ParameterBag\FrozenParameterBag;

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
        $this->parameters = $this->getDefaultParameters();

        $this->services =
        $this->scopedServices =
        $this->scopeStacks = array();
        $this->scopes = array();
        $this->scopeChildren = array();
        $this->methodMap = array(
            'test' => 'getTestService',
        );

        $this->aliases = array();
    }

    /**
     * {@inheritdoc}
     */
    public function compile()
    {
        throw new LogicException('You cannot compile a dumped frozen container.');
    }

    /**
     * {@inheritdoc}
     */
    public function isFrozen()
    {
        return true;
    }

    /**
     * Gets the public 'test' shared service.
     *
     * @return \stdClass
     */
    protected function getTestService()
    {
        return $this->services['test'] = new \stdClass(array('only dot' => '.', 'concatenation as value' => '.\'\'.', 'concatenation from the start value' => '\'\'.', '.' => 'dot as a key', '.\'\'.' => 'concatenation as a key', '\'\'.' => 'concatenation from the start key', 'optimize concatenation' => 'string1-string2', 'optimize concatenation with empty string' => 'string1string2', 'optimize concatenation from the start' => 'start', 'optimize concatenation at the end' => 'end', 'new line' => 'string with '."\n".'new line'));
    }

    /**
     * {@inheritdoc}
     */
    public function getParameter($name)
    {
        $name = strtolower($name);

        if (!(isset($this->parameters[$name]) || array_key_exists($name, $this->parameters))) {
            throw new InvalidArgumentException(sprintf('The parameter "%s" must be defined.', $name));
        }

        return $this->parameters[$name];
    }

    /**
     * {@inheritdoc}
     */
    public function hasParameter($name)
    {
        $name = strtolower($name);

        return isset($this->parameters[$name]) || array_key_exists($name, $this->parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function setParameter($name, $value)
    {
        throw new LogicException('Impossible to call set() on a frozen ParameterBag.');
    }

    /**
     * {@inheritdoc}
     */
    public function getParameterBag()
    {
        if (null === $this->parameterBag) {
            $this->parameterBag = new FrozenParameterBag($this->parameters);
        }

        return $this->parameterBag;
    }

    /**
     * Gets the default parameters.
     *
     * @return array An array of the default parameters
     */
    protected function getDefaultParameters()
    {
        return array(
            'empty_value' => '',
            'some_string' => '-',
        );
    }
}
