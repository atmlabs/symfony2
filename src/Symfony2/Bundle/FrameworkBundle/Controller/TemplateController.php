<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\FrameworkBundle\Controller;

use Symfony2\Component\DependencyInjection\ContainerAware;
use Symfony2\Component\HttpFoundation\Response;

/**
 * TemplateController.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class TemplateController extends ContainerAware
{
    /**
     * Renders a template.
     *
     * @param string    $template  The template name
     * @param int|null  $maxAge    Max age for client caching
     * @param int|null  $sharedAge Max age for shared (proxy) caching
     * @param bool|null $private   Whether or not caching should apply for client caches only
     *
     * @return Response A Response instance
     */
    public function templateAction($template, $maxAge = null, $sharedAge = null, $private = null)
    {
        if ($this->container->has('templating')) {
            $response = $this->container->get('templating')->renderResponse($template);
        } elseif ($this->container->has('twig')) {
            $response = new Response($this->container->get('twig')->render($template));
        } else {
            throw new \LogicException('You can not use the TemplateController if the Templating Component or the Twig Bundle are not available.');
        }

        if ($maxAge) {
            $response->setMaxAge($maxAge);
        }

        if ($sharedAge) {
            $response->setSharedMaxAge($sharedAge);
        }

        if ($private) {
            $response->setPrivate();
        } elseif (false === $private || (null === $private && ($maxAge || $sharedAge))) {
            $response->setPublic();
        }

        return $response;
    }
}
