<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bridge\Twig\Tests\Node;

use PHPUnit\Framework\TestCase;
use Symfony2\Bridge\Twig\Node\DumpNode;
use Twig\Compiler;
use Twig\Environment;
use Twig\Node\Expression\NameExpression;
use Twig\Node\Node;

class DumpNodeTest extends TestCase
{
    public function testNoVar()
    {
        $node = new DumpNode('bar', null, 7);

        $env = new Environment($this->getMockBuilder('Twig\Loader\LoaderInterface')->getMock());
        $compiler = new Compiler($env);

        $expected = <<<'EOTXT'
if ($this->env->isDebug()) {
    $barvars = array();
    foreach ($context as $barkey => $barval) {
        if (!$barval instanceof \Twig\Template) {
            $barvars[$barkey] = $barval;
        }
    }
    // line 7
    \Symfony2\Component\VarDumper\VarDumper::dump($barvars);
}

EOTXT;

        $this->assertSame($expected, $compiler->compile($node)->getSource());
    }

    public function testIndented()
    {
        $node = new DumpNode('bar', null, 7);

        $env = new Environment($this->getMockBuilder('Twig\Loader\LoaderInterface')->getMock());
        $compiler = new Compiler($env);

        $expected = <<<'EOTXT'
    if ($this->env->isDebug()) {
        $barvars = array();
        foreach ($context as $barkey => $barval) {
            if (!$barval instanceof \Twig\Template) {
                $barvars[$barkey] = $barval;
            }
        }
        // line 7
        \Symfony2\Component\VarDumper\VarDumper::dump($barvars);
    }

EOTXT;

        $this->assertSame($expected, $compiler->compile($node, 1)->getSource());
    }

    public function testOneVar()
    {
        $vars = new Node(array(
            new NameExpression('foo', 7),
        ));
        $node = new DumpNode('bar', $vars, 7);

        $env = new Environment($this->getMockBuilder('Twig\Loader\LoaderInterface')->getMock());
        $compiler = new Compiler($env);

        $expected = <<<'EOTXT'
if ($this->env->isDebug()) {
    // line 7
    \Symfony2\Component\VarDumper\VarDumper::dump(%foo%);
}

EOTXT;
        if (\PHP_VERSION_ID >= 70000) {
            $expected = preg_replace('/%(.*?)%/', '($context["$1"] ?? null)', $expected);
        } elseif (\PHP_VERSION_ID >= 50400) {
            $expected = preg_replace('/%(.*?)%/', '(isset($context["$1"]) ? $context["$1"] : null)', $expected);
        } else {
            $expected = preg_replace('/%(.*?)%/', '$this->getContext($context, "$1")', $expected);
        }

        $this->assertSame($expected, $compiler->compile($node)->getSource());
    }

    public function testMultiVars()
    {
        $vars = new Node(array(
            new NameExpression('foo', 7),
            new NameExpression('bar', 7),
        ));
        $node = new DumpNode('bar', $vars, 7);

        $env = new Environment($this->getMockBuilder('Twig\Loader\LoaderInterface')->getMock());
        $compiler = new Compiler($env);

        $expected = <<<'EOTXT'
if ($this->env->isDebug()) {
    // line 7
    \Symfony2\Component\VarDumper\VarDumper::dump(array(
        "foo" => %foo%,
        "bar" => %bar%,
    ));
}

EOTXT;

        if (\PHP_VERSION_ID >= 70000) {
            $expected = preg_replace('/%(.*?)%/', '($context["$1"] ?? null)', $expected);
        } elseif (\PHP_VERSION_ID >= 50400) {
            $expected = preg_replace('/%(.*?)%/', '(isset($context["$1"]) ? $context["$1"] : null)', $expected);
        } else {
            $expected = preg_replace('/%(.*?)%/', '$this->getContext($context, "$1")', $expected);
        }

        $this->assertSame($expected, $compiler->compile($node)->getSource());
    }
}
