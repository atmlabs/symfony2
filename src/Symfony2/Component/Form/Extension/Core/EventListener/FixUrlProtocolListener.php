<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Form\Extension\Core\EventListener;

use Symfony2\Component\EventDispatcher\EventSubscriberInterface;
use Symfony2\Component\Form\FormEvent;
use Symfony2\Component\Form\FormEvents;

/**
 * Adds a protocol to a URL if it doesn't already have one.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FixUrlProtocolListener implements EventSubscriberInterface
{
    private $defaultProtocol;

    /**
     * @param string|null $defaultProtocol The URL scheme to add when there is none or null to not modify the data
     */
    public function __construct($defaultProtocol = 'http')
    {
        $this->defaultProtocol = $defaultProtocol;
    }

    public function onSubmit(FormEvent $event)
    {
        $data = $event->getData();

        if ($this->defaultProtocol && $data && \is_string($data) && !preg_match('~^[\w+.-]+://~', $data)) {
            $event->setData($this->defaultProtocol.'://'.$data);
        }
    }

    /**
     * Alias of {@link onSubmit()}.
     *
     * @deprecated since version 2.3, to be removed in 3.0.
     *             Use {@link onSubmit()} instead.
     */
    public function onBind(FormEvent $event)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since Symfony 2.3 and will be removed in 3.0. Use the onSubmit() method instead.', E_USER_DEPRECATED);

        $this->onSubmit($event);
    }

    public static function getSubscribedEvents()
    {
        return array(FormEvents::SUBMIT => 'onSubmit');
    }
}
