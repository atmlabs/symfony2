<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\FrameworkBundle\EventListener;

use Symfony2\Component\DependencyInjection\ContainerInterface;
use Symfony2\Component\HttpKernel\EventListener\SessionListener as BaseSessionListener;

/**
 * Sets the session in the request.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class SessionListener extends BaseSessionListener
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    protected function getSession()
    {
        if (!$this->container->has('session')) {
            return;
        }

        return $this->container->get('session');
    }
}
