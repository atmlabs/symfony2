<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\SecurityBundle\Tests\Functional\Bundle\FormLoginBundle\Controller;

use Symfony2\Component\DependencyInjection\ContainerAware;
use Symfony2\Component\HttpFoundation\Request;
use Symfony2\Component\HttpFoundation\Response;
use Symfony2\Component\Security\Core\Security;

class LocalizedController extends ContainerAware
{
    public function loginAction(Request $request)
    {
        // get the login error if there is one
        if ($request->attributes->has(Security::AUTHENTICATION_ERROR)) {
            $error = $request->attributes->get(Security::AUTHENTICATION_ERROR);
        } else {
            $error = $request->getSession()->get(Security::AUTHENTICATION_ERROR);
        }

        return $this->container->get('templating')->renderResponse('FormLoginBundle:Localized:login.html.twig', array(
            // last username entered by the user
            'last_username' => $request->getSession()->get(Security::LAST_USERNAME),
            'error' => $error,
        ));
    }

    public function loginCheckAction()
    {
        throw new \RuntimeException('loginCheckAction() should never be called.');
    }

    public function logoutAction()
    {
        throw new \RuntimeException('logoutAction() should never be called.');
    }

    public function secureAction()
    {
        throw new \RuntimeException('secureAction() should never be called.');
    }

    public function profileAction()
    {
        return new Response('Profile');
    }

    public function homepageAction()
    {
        return new Response('Homepage');
    }
}
