<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bridge\ProxyManager\Tests\LazyProxy\PhpDumper;

use PHPUnit\Framework\TestCase;
use Symfony2\Bridge\ProxyManager\LazyProxy\PhpDumper\ProxyDumper;
use Symfony2\Component\DependencyInjection\Definition;

/**
 * Tests for {@see \Symfony2\Bridge\ProxyManager\LazyProxy\PhpDumper\ProxyDumper}.
 *
 * @author Marco Pivetta <ocramius@gmail.com>
 */
class ProxyDumperTest extends TestCase
{
    /**
     * @var ProxyDumper
     */
    protected $dumper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->dumper = new ProxyDumper();
    }

    /**
     * @dataProvider getProxyCandidates
     *
     * @param Definition $definition
     * @param bool       $expected
     */
    public function testIsProxyCandidate(Definition $definition, $expected)
    {
        $this->assertSame($expected, $this->dumper->isProxyCandidate($definition));
    }

    public function testGetProxyCode()
    {
        $definition = new Definition(__CLASS__);

        $definition->setLazy(true);

        $code = $this->dumper->getProxyCode($definition);

        $this->assertStringMatchesFormat(
            '%Aclass SymfonyBridgeProxyManagerTestsLazyProxyPhpDumperProxyDumperTest%aextends%w'
                .'\Symfony2\Bridge\ProxyManager\Tests\LazyProxy\PhpDumper\ProxyDumperTest%a',
            $code
        );
    }

    public function testGetProxyFactoryCode()
    {
        $definition = new Definition(__CLASS__);

        $definition->setLazy(true);

        $code = $this->dumper->getProxyFactoryCode($definition, 'foo');

        $this->assertStringMatchesFormat(
            '%wif ($lazyLoad) {%w$container = $this;%wreturn $this->services[\'foo\'] =%s'
            .'SymfonyBridgeProxyManagerTestsLazyProxyPhpDumperProxyDumperTest_%s(%wfunction '
            .'(&$wrappedInstance, \ProxyManager\Proxy\LazyLoadingInterface $proxy) use ($container) {'
            .'%w$wrappedInstance = $container->getFooService(false);%w$proxy->setProxyInitializer(null);'
            .'%wreturn true;%w}%w);%w}%w',
            $code
        );
    }

    /**
     * @return array
     */
    public function getProxyCandidates()
    {
        $definitions = array(
            array(new Definition(__CLASS__), true),
            array(new Definition('stdClass'), true),
            array(new Definition(uniqid('foo', true)), false),
            array(new Definition(), false),
        );

        array_map(
            function ($definition) {
                $definition[0]->setLazy(true);
            },
            $definitions
        );

        return $definitions;
    }
}
