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
use Symfony2\Bundle\WebProfilerBundle\Command\ImportCommand;
use Symfony2\Component\Console\Helper\HelperSet;
use Symfony2\Component\Console\Tester\CommandTester;
use Symfony2\Component\HttpKernel\Profiler\Profile;

/**
 * @group legacy
 */
class ImportCommandTest extends TestCase
{
    public function testExecute()
    {
        $profiler = $this
            ->getMockBuilder('Symfony2\Component\HttpKernel\Profiler\Profiler')
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $profiler->expects($this->once())->method('import')->will($this->returnValue(new Profile('TOKEN')));

        $helperSet = new HelperSet();
        $helper = $this->getMockBuilder('Symfony2\Component\Console\Helper\FormatterHelper')->getMock();
        $helper->expects($this->any())->method('formatSection');
        $helperSet->set($helper, 'formatter');

        $command = new ImportCommand($profiler);
        $command->setHelperSet($helperSet);

        $commandTester = new CommandTester($command);
        $commandTester->execute(array('filename' => __DIR__.'/../Fixtures/profile.data'));
        $this->assertRegExp('/Profile "TOKEN" has been successfully imported\./', $commandTester->getDisplay());
    }
}
