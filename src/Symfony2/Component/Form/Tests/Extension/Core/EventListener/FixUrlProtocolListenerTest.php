<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Form\Tests\Extension\Core\EventListener;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\Form\Extension\Core\EventListener\FixUrlProtocolListener;
use Symfony2\Component\Form\FormEvent;

class FixUrlProtocolListenerTest extends TestCase
{
    public function testFixHttpUrl()
    {
        $data = 'www.symfony.com';
        $form = $this->getMockBuilder('Symfony2\Component\Form\Test\FormInterface')->getMock();
        $event = new FormEvent($form, $data);

        $filter = new FixUrlProtocolListener('http');
        $filter->onSubmit($event);

        $this->assertEquals('http://www.symfony.com', $event->getData());
    }

    public function testSkipKnownUrl()
    {
        $data = 'http://www.symfony.com';
        $form = $this->getMockBuilder('Symfony2\Component\Form\Test\FormInterface')->getMock();
        $event = new FormEvent($form, $data);

        $filter = new FixUrlProtocolListener('http');
        $filter->onSubmit($event);

        $this->assertEquals('http://www.symfony.com', $event->getData());
    }

    public function provideUrlsWithSupportedProtocols()
    {
        return array(
            array('ftp://www.symfony.com'),
            array('chrome-extension://foo'),
            array('h323://foo'),
            array('iris.beep://foo'),
            array('foo+bar://foo'),
        );
    }

    /**
     * @dataProvider provideUrlsWithSupportedProtocols
     */
    public function testSkipOtherProtocol($url)
    {
        $form = $this->getMockBuilder('Symfony2\Component\Form\Test\FormInterface')->getMock();
        $event = new FormEvent($form, $url);

        $filter = new FixUrlProtocolListener('http');
        $filter->onSubmit($event);

        $this->assertEquals($url, $event->getData());
    }
}
