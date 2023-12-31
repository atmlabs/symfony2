<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Process\Tests;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\Process\PhpExecutableFinder;
use Symfony2\Component\Process\PhpProcess;

class PhpProcessTest extends TestCase
{
    public function testNonBlockingWorks()
    {
        $expected = 'hello world!';
        $process = new PhpProcess(<<<PHP
<?php echo '$expected';
PHP
        );
        $process->start();
        $process->wait();
        $this->assertEquals($expected, $process->getOutput());
    }

    public function testCommandLine()
    {
        $process = new PhpProcess(<<<'PHP'
<?php echo 'foobar';
PHP
        );

        $commandLine = $process->getCommandLine();

        $f = new PhpExecutableFinder();
        $this->assertContains($f->find(), $commandLine, '::getCommandLine() returns the command line of PHP before start');

        $process->start();
        $this->assertContains($commandLine, $process->getCommandLine(), '::getCommandLine() returns the command line of PHP after start');

        $process->wait();
        $this->assertContains($commandLine, $process->getCommandLine(), '::getCommandLine() returns the command line of PHP after wait');
    }
}
