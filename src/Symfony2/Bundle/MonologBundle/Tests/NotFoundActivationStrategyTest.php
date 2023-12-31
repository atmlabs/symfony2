<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\MonologBundle\Tests;

use Symfony2\Bundle\MonologBundle\NotFoundActivationStrategy;
use Symfony2\Component\HttpFoundation\Request;
use Symfony2\Component\HttpKernel\Exception\HttpException;
use Monolog\Logger;

class NotFoundActivationStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider isActivatedProvider
     */
    public function testIsActivated($url, $record, $expected)
    {
        $strategy = new NotFoundActivationStrategy(array('^/foo', 'bar'), Logger::WARNING);
        $strategy->setRequest(Request::create($url));

        $this->assertEquals($expected, $strategy->isHandlerActivated($record));
    }

    public function isActivatedProvider()
    {
        return array(
            array('/test',      array('level' => Logger::DEBUG), false),
            array('/foo',       array('level' => Logger::DEBUG, 'context' => $this->getContextException(404)), false),
            array('/baz/bar',   array('level' => Logger::ERROR, 'context' => $this->getContextException(404)), false),
            array('/foo',       array('level' => Logger::ERROR, 'context' => $this->getContextException(404)), false),
            array('/foo',       array('level' => Logger::ERROR, 'context' => $this->getContextException(500)), true),

            array('/test',      array('level' => Logger::ERROR), true),
            array('/baz',       array('level' => Logger::ERROR, 'context' => $this->getContextException(404)), true),
            array('/baz',       array('level' => Logger::ERROR, 'context' => $this->getContextException(500)), true),
        );
    }

    protected function getContextException($code)
    {
        return array('exception' => new HttpException($code));
    }
}
