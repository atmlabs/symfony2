<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\TwigBundle\Controller;

use Symfony2\Component\Debug\Exception\FlattenException;
use Symfony2\Component\HttpFoundation\Request;
use Symfony2\Component\HttpKernel\HttpKernelInterface;

/**
 * PreviewErrorController can be used to test error pages.
 *
 * It will create a test exception and forward it to another controller.
 *
 * @author Matthias Pigulla <mp@webfactory.de>
 */
class PreviewErrorController
{
    protected $kernel;
    protected $controller;

    public function __construct(HttpKernelInterface $kernel, $controller)
    {
        $this->kernel = $kernel;
        $this->controller = $controller;
    }

    public function previewErrorPageAction(Request $request, $code)
    {
        $exception = FlattenException::create(new \Exception('Something has intentionally gone wrong.'), $code);

        /*
         * This Request mimics the parameters set by
         * \Symfony2\Component\HttpKernel\EventListener\ExceptionListener::duplicateRequest, with
         * the additional "showException" flag.
         */

        $subRequest = $request->duplicate(null, null, array(
            '_controller' => $this->controller,
            'exception' => $exception,
            'logger' => null,
            'format' => $request->getRequestFormat(),
            'showException' => false,
        ));

        return $this->kernel->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
    }
}
