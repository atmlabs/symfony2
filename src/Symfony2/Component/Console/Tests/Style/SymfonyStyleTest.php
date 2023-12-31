<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Console\Tests\Style;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\Console\Command\Command;
use Symfony2\Component\Console\Input\InputInterface;
use Symfony2\Component\Console\Output\OutputInterface;
use Symfony2\Component\Console\Style\SymfonyStyle;
use Symfony2\Component\Console\Tester\CommandTester;

class SymfonyStyleTest extends TestCase
{
    /** @var Command */
    protected $command;
    /** @var CommandTester */
    protected $tester;

    protected function setUp()
    {
        $this->command = new Command('sfstyle');
        $this->tester = new CommandTester($this->command);
    }

    protected function tearDown()
    {
        $this->command = null;
        $this->tester = null;
    }

    /**
     * @dataProvider inputCommandToOutputFilesProvider
     */
    public function testOutputs($inputCommandFilepath, $outputFilepath)
    {
        $code = require $inputCommandFilepath;
        $this->command->setCode($code);
        $this->tester->execute(array(), array('interactive' => false, 'decorated' => false));
        $this->assertStringEqualsFile($outputFilepath, $this->tester->getDisplay(true));
    }

    public function inputCommandToOutputFilesProvider()
    {
        $baseDir = __DIR__.'/../Fixtures/Style/SymfonyStyle';

        return array_map(null, glob($baseDir.'/command/command_*.php'), glob($baseDir.'/output/output_*.txt'));
    }
}

/**
 * Use this class in tests to force the line length
 * and ensure a consistent output for expectations.
 */
class SymfonyStyleWithForcedLineLength extends SymfonyStyle
{
    public function __construct(InputInterface $input, OutputInterface $output)
    {
        parent::__construct($input, $output);

        $ref = new \ReflectionProperty(get_parent_class($this), 'lineLength');
        $ref->setAccessible(true);
        $ref->setValue($this, 120);
    }
}
