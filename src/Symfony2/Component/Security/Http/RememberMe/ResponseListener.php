<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Security\Http\RememberMe;

use Symfony2\Component\EventDispatcher\EventSubscriberInterface;
use Symfony2\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony2\Component\HttpKernel\KernelEvents;

/**
 * Adds remember-me cookies to the Response.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class ResponseListener implements EventSubscriberInterface
{
    public function onKernelResponse(FilterResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();

        if ($request->attributes->has(RememberMeServicesInterface::COOKIE_ATTR_NAME)) {
            $response->headers->setCookie($request->attributes->get(RememberMeServicesInterface::COOKIE_ATTR_NAME));
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(KernelEvents::RESPONSE => 'onKernelResponse');
    }
}
