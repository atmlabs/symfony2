<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\HttpKernel;

/**
 * Contains all events thrown in the HttpKernel component.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
final class KernelEvents
{
    /**
     * The REQUEST event occurs at the very beginning of request
     * dispatching.
     *
     * This event allows you to create a response for a request before any
     * other code in the framework is executed. The event listener method
     * receives a Symfony2\Component\HttpKernel\Event\GetResponseEvent
     * instance.
     *
     * @Event
     */
    const REQUEST = 'kernel.request';

    /**
     * The EXCEPTION event occurs when an uncaught exception appears.
     *
     * This event allows you to create a response for a thrown exception or
     * to modify the thrown exception. The event listener method receives
     * a Symfony2\Component\HttpKernel\Event\GetResponseForExceptionEvent
     * instance.
     *
     * @Event
     */
    const EXCEPTION = 'kernel.exception';

    /**
     * The VIEW event occurs when the return value of a controller
     * is not a Response instance.
     *
     * This event allows you to create a response for the return value of the
     * controller. The event listener method receives a
     * Symfony2\Component\HttpKernel\Event\GetResponseForControllerResultEvent
     * instance.
     *
     * @Event
     */
    const VIEW = 'kernel.view';

    /**
     * The CONTROLLER event occurs once a controller was found for
     * handling a request.
     *
     * This event allows you to change the controller that will handle the
     * request. The event listener method receives a
     * Symfony2\Component\HttpKernel\Event\FilterControllerEvent instance.
     *
     * @Event
     */
    const CONTROLLER = 'kernel.controller';

    /**
     * The RESPONSE event occurs once a response was created for
     * replying to a request.
     *
     * This event allows you to modify or replace the response that will be
     * replied. The event listener method receives a
     * Symfony2\Component\HttpKernel\Event\FilterResponseEvent instance.
     *
     * @Event
     */
    const RESPONSE = 'kernel.response';

    /**
     * The TERMINATE event occurs once a response was sent.
     *
     * This event allows you to run expensive post-response jobs.
     * The event listener method receives a
     * Symfony2\Component\HttpKernel\Event\PostResponseEvent instance.
     *
     * @Event
     */
    const TERMINATE = 'kernel.terminate';

    /**
     * The FINISH_REQUEST event occurs when a response was generated for a request.
     *
     * This event allows you to reset the global and environmental state of
     * the application, when it was changed during the request.
     * The event listener method receives a
     * Symfony2\Component\HttpKernel\Event\FinishRequestEvent instance.
     *
     * @Event
     */
    const FINISH_REQUEST = 'kernel.finish_request';
}
