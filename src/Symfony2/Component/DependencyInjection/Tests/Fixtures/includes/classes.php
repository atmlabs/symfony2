<?php

use Symfony2\Component\DependencyInjection\Definition;
use Symfony2\Component\DependencyInjection\LazyProxy\PhpDumper\DumperInterface as ProxyDumper;

function sc_configure($instance)
{
    $instance->configure();
}

class BarClass extends BazClass
{
    protected $baz;
    public $foo = 'foo';

    public function setBaz(BazClass $baz)
    {
        $this->baz = $baz;
    }

    public function getBaz()
    {
        return $this->baz;
    }
}

class BazClass
{
    protected $foo;

    public function setFoo(Foo $foo)
    {
        $this->foo = $foo;
    }

    public function configure($instance)
    {
        $instance->configure();
    }

    public static function getInstance()
    {
        return new self();
    }

    public static function configureStatic($instance)
    {
        $instance->configure();
    }

    public static function configureStatic1()
    {
    }
}

class BarUserClass
{
    public $bar;

    public function __construct(BarClass $bar)
    {
        $this->bar = $bar;
    }
}

class MethodCallClass
{
    public $simple;
    public $complex;
    private $callPassed = false;

    public function callMe()
    {
        $this->callPassed = is_scalar($this->simple) && is_object($this->complex);
    }

    public function callPassed()
    {
        return $this->callPassed;
    }
}

class DummyProxyDumper implements ProxyDumper
{
    public function isProxyCandidate(Definition $definition)
    {
        return $definition->isLazy();
    }

    public function getProxyFactoryCode(Definition $definition, $id)
    {
        return "        // lazy factory\n\n";
    }

    public function getProxyCode(Definition $definition)
    {
        return "// proxy code\n";
    }
}
