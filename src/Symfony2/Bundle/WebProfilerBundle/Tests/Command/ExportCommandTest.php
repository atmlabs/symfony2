<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\WebProfilerBundle\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony2\Bundle\WebProfilerBundle\Command\ExportCommand;
use Symfony2\Component\Console\Helper\HelperSet;
use Symfony2\Component\Console\Tester\CommandTester;
use Symfony2\Component\HttpKernel\Profiler\Profile;

/**
 * @group legacy
 */
class ExportCommandTest extends TestCase
{
    /**
     * @expectedException \LogicException
     */
    public function testExecuteWithUnknownToken()
    {
        $profiler = $this
            ->getMockBuilder('Symfony2\Component\HttpKernel\Profiler\Profiler')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $helperSet = new HelperSet();
        $helper = $this->getMockBuilder('Symfony2\Component\Console\Helper\FormatterHelper')->getMock();
        $helper->expects($this->any())->method('formatSection');
        $helperSet->set($helper, 'formatter');

        $command = new ExportCommand($profiler);
        $command->setHelperSet($helperSet);

        $commandTester = new CommandTester($command);
        $commandTester->execute(array('token' => 'TOKEN'));
    }

    public function testExecuteWithToken()
    {
        $profiler = $this
            ->getMockBuilder('Symfony2\Component\HttpKernel\Profiler\Profiler')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $profile = new Profile('TOKEN');
        $profiler->expects($this->once())->method('loadProfile')->with('TOKEN')->will($this->returnValue($profile));

        $helperSet = new HelperSet();
        $helper = $this->getMockBuilder('Symfony2\Component\Console\Helper\FormatterHelper')->getMock();
        $helper->expects($this->any())->method('formatSection');
        $helperSet->set($helper, 'formatter');

        $command = new ExportCommand($profiler);
        $command->setHelperSet($helperSet);

        $commandTester = new CommandTester($command);
        $commandTester->execute(array('token' => 'TOKEN'));
        $this->assertEquals($profiler->export($profile), $commandTester->getDisplay());
    }
}
