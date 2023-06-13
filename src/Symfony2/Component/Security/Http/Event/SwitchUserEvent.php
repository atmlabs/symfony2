<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Security\Http\Event;

use Symfony2\Component\EventDispatcher\Event;
use Symfony2\Component\HttpFoundation\Request;
use Symfony2\Component\Security\Core\User\UserInterface;

/**
 * SwitchUserEvent.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SwitchUserEvent extends Event
{
    private $request;
    private $targetUser;

    public function __construct(Request $request, UserInterface $targetUser)
    {
        $this->request = $request;
        $this->targetUser = $targetUser;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @return UserInterface
     */
    public function getTargetUser()
    {
        return $this->targetUser;
    }
}
