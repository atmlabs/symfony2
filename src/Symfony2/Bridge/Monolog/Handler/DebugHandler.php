<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bridge\Monolog\Handler;

use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Symfony2\Component\HttpKernel\Log\DebugLoggerInterface;

/**
 * DebugLogger.
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class DebugHandler extends TestHandler implements DebugLoggerInterface
{
    /**
     * {@inheritdoc}
     */
    public function getLogs()
    {
        $records = array();
        foreach ($this->records as $record) {
            $records[] = array(
                'timestamp' => $record['datetime']->getTimestamp(),
                'message' => $record['message'],
                'priority' => $record['level'],
                'priorityName' => $record['level_name'],
                'context' => $record['context'],
                'channel' => isset($record['channel']) ? $record['channel'] : '',
            );
        }

        return $records;
    }

    /**
     * {@inheritdoc}
     */
    public function countErrors()
    {
        $cnt = 0;
        $levels = array(Logger::ERROR, Logger::CRITICAL, Logger::ALERT, Logger::EMERGENCY);
        foreach ($levels as $level) {
            if (isset($this->recordsByLevel[$level])) {
                $cnt += \count($this->recordsByLevel[$level]);
            }
        }

        return $cnt;
    }
}
