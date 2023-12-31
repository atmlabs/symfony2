<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\DependencyInjection\Tests\Dumper;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\DependencyInjection\ContainerBuilder;
use Symfony2\Component\DependencyInjection\Definition;
use Symfony2\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony2\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony2\Component\DependencyInjection\Reference;
use Symfony2\Component\DependencyInjection\Variable;
use Symfony2\Component\ExpressionLanguage\Expression;

require_once __DIR__.'/../Fixtures/includes/classes.php';

class PhpDumperTest extends TestCase
{
    protected static $fixturesPath;

    public static function setUpBeforeClass()
    {
        self::$fixturesPath = realpath(__DIR__.'/../Fixtures/');
    }

    public function testDump()
    {
        $dumper = new PhpDumper(new ContainerBuilder());

        $this->assertStringEqualsFile(self::$fixturesPath.'/php/services1.php', $dumper->dump(), '->dump() dumps an empty container as an empty PHP class');
        $this->assertStringEqualsFile(self::$fixturesPath.'/php/services1-1.php', $dumper->dump(array('class' => 'Container', 'base_class' => 'AbstractContainer', 'namespace' => 'Symfony2\Component\DependencyInjection\Dump')), '->dump() takes a class and a base_class options');
    }

    public function testDumpOptimizationString()
    {
        $definition = new Definition();
        $definition->setClass('stdClass');
        $definition->addArgument(array(
            'only dot' => '.',
            'concatenation as value' => '.\'\'.',
            'concatenation from the start value' => '\'\'.',
            '.' => 'dot as a key',
            '.\'\'.' => 'concatenation as a key',
            '\'\'.' => 'concatenation from the start key',
            'optimize concatenation' => 'string1%some_string%string2',
            'optimize concatenation with empty string' => 'string1%empty_value%string2',
            'optimize concatenation from the start' => '%empty_value%start',
            'optimize concatenation at the end' => 'end%empty_value%',
            'new line' => "string with \nnew line",
        ));

        $container = new ContainerBuilder();
        $container->setResourceTracking(false);
        $container->setDefinition('test', $definition);
        $container->setParameter('empty_value', '');
        $container->setParameter('some_string', '-');
        $container->compile();

        $dumper = new PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath.'/php/services10.php', $dumper->dump(), '->dump() dumps an empty container as an empty PHP class');
    }

    public function testDumpRelativeDir()
    {
        $definition = new Definition();
        $definition->setClass('stdClass');
        $definition->addArgument('%foo%');
        $definition->addArgument(array('%foo%' => '%buz%/'));

        $container = new ContainerBuilder();
        $container->setDefinition('test', $definition);
        $container->setParameter('foo', 'wiz'.\dirname(__DIR__));
        $container->setParameter('bar', __DIR__);
        $container->setParameter('baz', '%bar%/PhpDumperTest.php');
        $container->setParameter('buz', \dirname(\dirname(__DIR__)));
        $container->compile();

        $dumper = new PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath.'/php/services12.php', $dumper->dump(array('file' => __FILE__)), '->dump() dumps __DIR__ relative strings');
    }

    /**
     * @dataProvider provideInvalidParameters
     * @expectedException \InvalidArgumentException
     */
    public function testExportParameters($parameters)
    {
        $dumper = new PhpDumper(new ContainerBuilder(new ParameterBag($parameters)));
        $dumper->dump();
    }

    public function provideInvalidParameters()
    {
        return array(
            array(array('foo' => new Definition('stdClass'))),
            array(array('foo' => new Expression('service("foo").foo() ~ (container.hasParameter("foo") ? parameter("foo") : "default")'))),
            array(array('foo' => new Reference('foo'))),
            array(array('foo' => new Variable('foo'))),
        );
    }

    public function testAddParameters()
    {
        $container = include self::$fixturesPath.'/containers/container8.php';
        $dumper = new PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath.'/php/services8.php', $dumper->dump(), '->dump() dumps parameters');
    }

    public function testAddService()
    {
        // without compilation
        $container = include self::$fixturesPath.'/containers/container9.php';
        $dumper = new PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath.'/php/services9.php', str_replace(str_replace('\\', '\\\\', self::$fixturesPath.\DIRECTORY_SEPARATOR.'includes'.\DIRECTORY_SEPARATOR), '%path%', $dumper->dump()), '->dump() dumps services');

        // with compilation
        $container = include self::$fixturesPath.'/containers/container9.php';
        $container->compile();
        $dumper = new PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath.'/php/services9_compiled.php', str_replace(str_replace('\\', '\\\\', self::$fixturesPath.\DIRECTORY_SEPARATOR.'includes'.\DIRECTORY_SEPARATOR), '%path%', $dumper->dump()), '->dump() dumps services');

        $dumper = new PhpDumper($container = new ContainerBuilder());
        $container->register('foo', 'FooClass')->addArgument(new \stdClass());
        try {
            $dumper->dump();
            $this->fail('->dump() throws a RuntimeException if the container to be dumped has reference to objects or resources');
        } catch (\Exception $e) {
            $this->assertInstanceOf('\Symfony2\Component\DependencyInjection\Exception\RuntimeException', $e, '->dump() throws a RuntimeException if the container to be dumped has reference to objects or resources');
            $this->assertEquals('Unable to dump a service container if a parameter is an object or a resource.', $e->getMessage(), '->dump() throws a RuntimeException if the container to be dumped has reference to objects or resources');
        }
    }

    /**
     * @group legacy
     */
    public function testLegacySynchronizedServices()
    {
        $container = include self::$fixturesPath.'/containers/container20.php';
        $dumper = new PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath.'/php/services20.php', str_replace(str_replace('\\', '\\\\', self::$fixturesPath.\DIRECTORY_SEPARATOR.'includes'.\DIRECTORY_SEPARATOR), '%path%', $dumper->dump()), '->dump() dumps services');
    }

    public function testServicesWithAnonymousFactories()
    {
        $container = include self::$fixturesPath.'/containers/container19.php';
        $dumper = new PhpDumper($container);

        $this->assertStringEqualsFile(self::$fixturesPath.'/php/services19.php', $dumper->dump(), '->dump() dumps services with anonymous factories');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Service id "bar$" cannot be converted to a valid PHP method name.
     */
    public function testAddServiceInvalidServiceId()
    {
        $container = new ContainerBuilder();
        $container->register('bar$', 'FooClass');
        $dumper = new PhpDumper($container);
        $dumper->dump();
    }

    /**
     * @dataProvider provideInvalidFactories
     * @expectedException \Symfony2\Component\DependencyInjection\Exception\RuntimeException
     * @expectedExceptionMessage Cannot dump definition
     */
    public function testInvalidFactories($factory)
    {
        $container = new ContainerBuilder();
        $def = new Definition('stdClass');
        $def->setFactory($factory);
        $container->setDefinition('bar', $def);
        $dumper = new PhpDumper($container);
        $dumper->dump();
    }

    public function provideInvalidFactories()
    {
        return array(
            array(array('', 'method')),
            array(array('class', '')),
            array(array('...', 'method')),
            array(array('class', '...')),
        );
    }

    public function testAliases()
    {
        $container = include self::$fixturesPath.'/containers/container9.php';
        $container->compile();
        $dumper = new PhpDumper($container);
        eval('?>'.$dumper->dump(array('class' => 'Symfony_DI_PhpDumper_Test_Aliases')));

        $container = new \Symfony_DI_PhpDumper_Test_Aliases();
        $container->set('foo', $foo = new \stdClass());
        $this->assertSame($foo, $container->get('foo'));
        $this->assertSame($foo, $container->get('alias_for_foo'));
        $this->assertSame($foo, $container->get('alias_for_alias'));
    }

    public function testFrozenContainerWithoutAliases()
    {
        $container = new ContainerBuilder();
        $container->compile();

        $dumper = new PhpDumper($container);
        eval('?>'.$dumper->dump(array('class' => 'Symfony_DI_PhpDumper_Test_Frozen_No_Aliases')));

        $container = new \Symfony_DI_PhpDumper_Test_Frozen_No_Aliases();
        $this->assertFalse($container->has('foo'));
    }

    public function testOverrideServiceWhenUsingADumpedContainer()
    {
        require_once self::$fixturesPath.'/php/services9.php';
        require_once self::$fixturesPath.'/includes/foo.php';

        $container = new \ProjectServiceContainer();
        $container->set('bar', $bar = new \stdClass());
        $container->setParameter('foo_bar', 'foo_bar');

        $this->assertSame($bar, $container->get('bar'), '->set() overrides an already defined service');
    }

    public function testOverrideServiceWhenUsingADumpedContainerAndServiceIsUsedFromAnotherOne()
    {
        require_once self::$fixturesPath.'/php/services9.php';
        require_once self::$fixturesPath.'/includes/foo.php';
        require_once self::$fixturesPath.'/includes/classes.php';

        $container = new \ProjectServiceContainer();
        $container->set('bar', $bar = new \stdClass());

        $this->assertSame($bar, $container->get('foo')->bar, '->set() overrides an already defined service');
    }

    /**
     * @expectedException \Symfony2\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     */
    public function testCircularReference()
    {
        $container = new ContainerBuilder();
        $container->register('foo', 'stdClass')->addArgument(new Reference('bar'));
        $container->register('bar', 'stdClass')->setPublic(false)->addMethodCall('setA', array(new Reference('baz')));
        $container->register('baz', 'stdClass')->addMethodCall('setA', array(new Reference('foo')));
        $container->compile();

        $dumper = new PhpDumper($container);
        $dumper->dump();
    }

    public function testDumpAutowireData()
    {
        $container = include self::$fixturesPath.'/containers/container24.php';
        $dumper = new PhpDumper($container);

        $this->assertStringEqualsFile(self::$fixturesPath.'/php/services24.php', $dumper->dump());
    }

    public function testInlinedDefinitionReferencingServiceContainer()
    {
        $container = new ContainerBuilder();
        $container->register('foo', 'stdClass')->addMethodCall('add', array(new Reference('service_container')))->setPublic(false);
        $container->register('bar', 'stdClass')->addArgument(new Reference('foo'));
        $container->compile();

        $dumper = new PhpDumper($container);
        $this->assertStringEqualsFile(self::$fixturesPath.'/php/services13.php', $dumper->dump(), '->dump() dumps inline definitions which reference service_container');
    }

    public function testNonSharedLazyDefinitionReferences()
    {
        $container = new ContainerBuilder();
        $container->register('foo', 'stdClass')->setShared(false)->setLazy(true);
        $container->register('bar', 'stdClass')->addArgument(new Reference('foo', ContainerBuilder::EXCEPTION_ON_INVALID_REFERENCE, false));
        $container->compile();

        $dumper = new PhpDumper($container);
        $dumper->setProxyDumper(new \DummyProxyDumper());

        $this->assertStringEqualsFile(self::$fixturesPath.'/php/services_non_shared_lazy.php', $dumper->dump());
    }

    public function testInitializePropertiesBeforeMethodCalls()
    {
        require_once self::$fixturesPath.'/includes/classes.php';

        $container = new ContainerBuilder();
        $container->register('foo', 'stdClass');
        $container->register('bar', 'MethodCallClass')
            ->setProperty('simple', 'bar')
            ->setProperty('complex', new Reference('foo'))
            ->addMethodCall('callMe');
        $container->compile();

        $dumper = new PhpDumper($container);
        eval('?>'.$dumper->dump(array('class' => 'Symfony_DI_PhpDumper_Test_Properties_Before_Method_Calls')));

        $container = new \Symfony_DI_PhpDumper_Test_Properties_Before_Method_Calls();
        $this->assertTrue($container->get('bar')->callPassed(), '->dump() initializes properties before method calls');
    }

    public function testCircularReferenceAllowanceForLazyServices()
    {
        $container = new ContainerBuilder();
        $container->register('foo', 'stdClass')->addArgument(new Reference('bar'));
        $container->register('bar', 'stdClass')->setLazy(true)->addArgument(new Reference('foo'));
        $container->compile();

        $dumper = new PhpDumper($container);
        $dumper->dump();

        $this->addToAssertionCount(1);
    }

    public function testCircularReferenceAllowanceForInlinedDefinitionsForLazyServices()
    {
        /*
         *   test graph:
         *              [connection] -> [event_manager] --> [entity_manager](lazy)
         *                                                           |
         *                                                           --(call)- addEventListener ("@lazy_service")
         *
         *              [lazy_service](lazy) -> [entity_manager](lazy)
         *
         */

        $container = new ContainerBuilder();

        $eventManagerDefinition = new Definition('stdClass');

        $connectionDefinition = $container->register('connection', 'stdClass');
        $connectionDefinition->addArgument($eventManagerDefinition);

        $container->register('entity_manager', 'stdClass')
            ->setLazy(true)
            ->addArgument(new Reference('connection'));

        $lazyServiceDefinition = $container->register('lazy_service', 'stdClass');
        $lazyServiceDefinition->setLazy(true);
        $lazyServiceDefinition->addArgument(new Reference('entity_manager'));

        $eventManagerDefinition->addMethodCall('addEventListener', array(new Reference('lazy_service')));

        $container->compile();

        $dumper = new PhpDumper($container);

        $dumper->setProxyDumper(new \DummyProxyDumper());
        $dumper->dump();

        $this->addToAssertionCount(1);
    }

    public function testDumpHandlesLiteralClassWithRootNamespace()
    {
        $container = new ContainerBuilder();
        $container->register('foo', '\\stdClass');

        $dumper = new PhpDumper($container);
        eval('?>'.$dumper->dump(array('class' => 'Symfony_DI_PhpDumper_Test_Literal_Class_With_Root_Namespace')));

        $container = new \Symfony_DI_PhpDumper_Test_Literal_Class_With_Root_Namespace();

        $this->assertInstanceOf('stdClass', $container->get('foo'));
    }

    /**
     * This test checks the trigger of a deprecation note and should not be removed in major releases.
     *
     * @group legacy
     * @expectedDeprecation The "foo" service is deprecated. You should stop using it, as it will soon be removed.
     */
    public function testPrivateServiceTriggersDeprecation()
    {
        $container = new ContainerBuilder();
        $container->register('foo', 'stdClass')
            ->setPublic(false)
            ->setDeprecated(true);
        $container->register('bar', 'stdClass')
            ->setPublic(true)
            ->setProperty('foo', new Reference('foo'));

        $container->compile();

        $dumper = new PhpDumper($container);
        eval('?>'.$dumper->dump(array('class' => 'Symfony_DI_PhpDumper_Test_Private_Service_Triggers_Deprecation')));

        $container = new \Symfony_DI_PhpDumper_Test_Private_Service_Triggers_Deprecation();

        $container->get('bar');
    }
}
