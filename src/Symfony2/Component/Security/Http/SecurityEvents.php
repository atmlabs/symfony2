<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Security\Http;

final class SecurityEvents
{
    /**
     * The INTERACTIVE_LOGIN event occurs after a user has actively logged
     * into your website. It is important to distinguish this action from
     * non-interactive authentication methods, such as:
     *   - authentication based on your session.
     *   - authentication using a HTTP basic or HTTP digest header.
     *
     * The event listener method receives a
     * Symfony2\Component\Security\Http\Event\InteractiveLoginEvent instance.
     *
     * @Event
     */
    const INTERACTIVE_LOGIN = 'security.interactive_login';

    /**
     * The SWITCH_USER event occurs before switch to another user and
     * before exit from an already switched user.
     *
     * The event listener method receives a
     * Symfony2\Component\Security\Http\Event\SwitchUserEvent instance.
     *
     * @Event
     */
    const SWITCH_USER = 'security.switch_user';
}
