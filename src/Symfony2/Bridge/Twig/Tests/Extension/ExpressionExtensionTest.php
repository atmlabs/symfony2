<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bridge\Twig\Tests\Extension;

use PHPUnit\Framework\TestCase;
use Symfony2\Bridge\Twig\Extension\ExpressionExtension;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

class ExpressionExtensionTest extends TestCase
{
    protected $helper;

    public function testExpressionCreation()
    {
        $template = "{{ expression('1 == 1') }}";
        $twig = new Environment(new ArrayLoader(array('template' => $template)), array('debug' => true, 'cache' => false, 'autoescape' => 'html', 'optimizations' => 0));
        $twig->addExtension(new ExpressionExtension());

        $output = $twig->render('template');
        $this->assertEquals('1 == 1', $output);
    }
}
