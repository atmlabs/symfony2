<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\HttpKernel\EventListener;

use Symfony2\Component\Console\ConsoleEvents;
use Symfony2\Component\EventDispatcher\EventSubscriberInterface;
use Symfony2\Component\VarDumper\Cloner\ClonerInterface;
use Symfony2\Component\VarDumper\Dumper\DataDumperInterface;
use Symfony2\Component\VarDumper\VarDumper;

/**
 * Configures dump() handler.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
class DumpListener implements EventSubscriberInterface
{
    private $cloner;
    private $dumper;

    public function __construct(ClonerInterface $cloner, DataDumperInterface $dumper)
    {
        $this->cloner = $cloner;
        $this->dumper = $dumper;
    }

    public function configure()
    {
        $cloner = $this->cloner;
        $dumper = $this->dumper;

        VarDumper::setHandler(function ($var) use ($cloner, $dumper) {
            $dumper->dump($cloner->cloneVar($var));
        });
    }

    public static function getSubscribedEvents()
    {
        // Register early to have a working dump() as early as possible
        return array(ConsoleEvents::COMMAND => array('configure', 1024));
    }
}
