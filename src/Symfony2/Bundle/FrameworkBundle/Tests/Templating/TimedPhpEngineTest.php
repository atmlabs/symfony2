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

use Symfony2\Bundle\FrameworkBundle\Templating\GlobalVariables;
use Symfony2\Bundle\FrameworkBundle\Templating\TimedPhpEngine;
use Symfony2\Bundle\FrameworkBundle\Tests\TestCase;
use Symfony2\Component\DependencyInjection\Container;

class TimedPhpEngineTest extends TestCase
{
    public function testThatRenderLogsTime()
    {
        $container = $this->getContainer();
        $templateNameParser = $this->getTemplateNameParser();
        $globalVariables = $this->getGlobalVariables();
        $loader = $this->getLoader($this->getStorage());

        $stopwatch = $this->getStopwatch();
        $stopwatchEvent = $this->getStopwatchEvent();

        $stopwatch->expects($this->once())
            ->method('start')
            ->with('template.php (index.php)', 'template')
            ->will($this->returnValue($stopwatchEvent));

        $stopwatchEvent->expects($this->once())->method('stop');

        $engine = new TimedPhpEngine($templateNameParser, $container, $loader, $stopwatch, $globalVariables);
        $engine->render('index.php');
    }

    /**
     * @return Container
     */
    private function getContainer()
    {
        return $this->getMockBuilder('Symfony2\Component\DependencyInjection\Container')->getMock();
    }

    /**
     * @return \Symfony2\Component\Templating\TemplateNameParserInterface
     */
    private function getTemplateNameParser()
    {
        $templateReference = $this->getMockBuilder('Symfony2\Component\Templating\TemplateReferenceInterface')->getMock();
        $templateNameParser = $this->getMockBuilder('Symfony2\Component\Templating\TemplateNameParserInterface')->getMock();
        $templateNameParser->expects($this->any())
            ->method('parse')
            ->will($this->returnValue($templateReference));

        return $templateNameParser;
    }

    /**
     * @return GlobalVariables
     */
    private function getGlobalVariables()
    {
        return $this->getMockBuilder('Symfony2\Bundle\FrameworkBundle\Templating\GlobalVariables')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \Symfony2\Component\Templating\Storage\StringStorage
     */
    private function getStorage()
    {
        return $this->getMockBuilder('Symfony2\Component\Templating\Storage\StringStorage')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
    }

    /**
     * @param \Symfony2\Component\Templating\Storage\StringStorage $storage
     *
     * @return \Symfony2\Component\Templating\Loader\Loader
     */
    private function getLoader($storage)
    {
        $loader = $this->getMockForAbstractClass('Symfony2\Component\Templating\Loader\Loader');
        $loader->expects($this->once())
            ->method('load')
            ->will($this->returnValue($storage));

        return $loader;
    }

    /**
     * @return \Symfony2\Component\Stopwatch\StopwatchEvent
     */
    private function getStopwatchEvent()
    {
        return $this->getMockBuilder('Symfony2\Component\Stopwatch\StopwatchEvent')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return \Symfony2\Component\Stopwatch\Stopwatch
     */
    private function getStopwatch()
    {
        return $this->getMockBuilder('Symfony2\Component\Stopwatch\Stopwatch')->getMock();
    }
}
