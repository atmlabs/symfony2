<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Templating;

/**
 * DebuggerInterface is the interface you need to implement
 * to debug template loader instances.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @deprecated since version 2.4, to be removed in 3.0. Use Psr\Log\LoggerInterface instead.
 */
interface DebuggerInterface
{
    /**
     * Logs a message.
     *
     * @param string $message A message to log
     */
    public function log($message);
}
