<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\VarDumper;

use Symfony2\Component\VarDumper\Cloner\VarCloner;
use Symfony2\Component\VarDumper\Dumper\CliDumper;
use Symfony2\Component\VarDumper\Dumper\HtmlDumper;

// Load the global dump() function
require_once __DIR__.'/Resources/functions/dump.php';

/**
 * @author Nicolas Grekas <p@tchwork.com>
 */
class VarDumper
{
    private static $handler;

    public static function dump($var)
    {
        if (null === self::$handler) {
            $cloner = new VarCloner();
            $dumper = \in_array(\PHP_SAPI, array('cli', 'phpdbg'), true) ? new CliDumper() : new HtmlDumper();
            self::$handler = function ($var) use ($cloner, $dumper) {
                $dumper->dump($cloner->cloneVar($var));
            };
        }

        return \call_user_func(self::$handler, $var);
    }

    public static function setHandler($callable)
    {
        if (null !== $callable && !\is_callable($callable, true)) {
            throw new \InvalidArgumentException('Invalid PHP callback.');
        }

        $prevHandler = self::$handler;
        self::$handler = $callable;

        return $prevHandler;
    }
}
