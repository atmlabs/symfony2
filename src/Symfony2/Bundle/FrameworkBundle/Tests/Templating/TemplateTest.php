<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\FrameworkBundle\Tests\Templating;

use Symfony2\Bundle\FrameworkBundle\Templating\TemplateReference;
use Symfony2\Bundle\FrameworkBundle\Tests\TestCase;

class TemplateTest extends TestCase
{
    /**
     * @dataProvider getTemplateToPathProvider
     */
    public function testGetPathForTemplate($template, $path)
    {
        $this->assertSame($template->getPath(), $path);
    }

    public function getTemplateToPathProvider()
    {
        return array(
            array(new TemplateReference('FooBundle', 'Post', 'index', 'html', 'php'), '@FooBundle/Resources/views/Post/index.html.php'),
            array(new TemplateReference('FooBundle', '', 'index', 'html', 'twig'), '@FooBundle/Resources/views/index.html.twig'),
            array(new TemplateReference('', 'Post', 'index', 'html', 'php'), 'views/Post/index.html.php'),
            array(new TemplateReference('', '', 'index', 'html', 'php'), 'views/index.html.php'),
        );
    }
}
