<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\HttpKernel\EventListener;

use Psr\Log\LoggerInterface;
use Symfony2\Component\EventDispatcher\EventSubscriberInterface;
use Symfony2\Component\HttpFoundation\Request;
use Symfony2\Component\HttpFoundation\RequestStack;
use Symfony2\Component\HttpKernel\Event\FinishRequestEvent;
use Symfony2\Component\HttpKernel\Event\GetResponseEvent;
use Symfony2\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony2\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony2\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony2\Component\HttpKernel\KernelEvents;
use Symfony2\Component\Routing\Exception\MethodNotAllowedException;
use Symfony2\Component\Routing\Exception\ResourceNotFoundException;
use Symfony2\Component\Routing\Matcher\RequestMatcherInterface;
use Symfony2\Component\Routing\Matcher\UrlMatcherInterface;
use Symfony2\Component\Routing\RequestContext;
use Symfony2\Component\Routing\RequestContextAwareInterface;

/**
 * Initializes the context from the request and sets request attributes based on a matching route.
 *
 * This listener works in 2 modes:
 *
 *  * 2.3 compatibility mode where you must call setRequest whenever the Request changes.
 *  * 2.4+ mode where you must pass a RequestStack instance in the constructor.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class RouterListener implements EventSubscriberInterface
{
    private $matcher;
    private $context;
    private $logger;
    private $request;
    private $requestStack;

    /**
     * RequestStack will become required in 3.0.
     *
     * @param UrlMatcherInterface|RequestMatcherInterface $matcher      The Url or Request matcher
     * @param RequestStack                                $requestStack A RequestStack instance
     * @param RequestContext|null                         $context      The RequestContext (can be null when $matcher implements RequestContextAwareInterface)
     * @param LoggerInterface|null                        $logger       The logger
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($matcher, $requestStack = null, $context = null, $logger = null)
    {
        if ($requestStack instanceof RequestContext || $context instanceof LoggerInterface || $logger instanceof RequestStack) {
            $tmp = $requestStack;
            $requestStack = $logger;
            $logger = $context;
            $context = $tmp;

            @trigger_error('The '.__METHOD__.' method now requires a RequestStack to be given as second argument as '.__CLASS__.'::setRequest method will not be supported anymore in 3.0.', E_USER_DEPRECATED);
        } elseif (!$requestStack instanceof RequestStack) {
            @trigger_error('The '.__METHOD__.' method now requires a RequestStack instance as '.__CLASS__.'::setRequest method will not be supported anymore in 3.0.', E_USER_DEPRECATED);
        }

        if (null !== $requestStack && !$requestStack instanceof RequestStack) {
            throw new \InvalidArgumentException('RequestStack instance expected.');
        }
        if (null !== $context && !$context instanceof RequestContext) {
            throw new \InvalidArgumentException('RequestContext instance expected.');
        }
        if (null !== $logger && !$logger instanceof LoggerInterface) {
            throw new \InvalidArgumentException('Logger must implement LoggerInterface.');
        }

        if (!$matcher instanceof UrlMatcherInterface && !$matcher instanceof RequestMatcherInterface) {
            throw new \InvalidArgumentException('Matcher must either implement UrlMatcherInterface or RequestMatcherInterface.');
        }

        if (null === $context && !$matcher instanceof RequestContextAwareInterface) {
            throw new \InvalidArgumentException('You must either pass a RequestContext or the matcher must implement RequestContextAwareInterface.');
        }

        $this->matcher = $matcher;
        $this->context = $context ?: $matcher->getContext();
        $this->requestStack = $requestStack;
        $this->logger = $logger;
    }

    /**
     * Sets the current Request.
     *
     * This method was used to synchronize the Request, but as the HttpKernel
     * is doing that automatically now, you should never call it directly.
     * It is kept public for BC with the 2.3 version.
     *
     * @param Request|null $request A Request instance
     *
     * @deprecated since version 2.4, to be removed in 3.0.
     */
    public function setRequest(Request $request = null)
    {
        @trigger_error('The '.__METHOD__.' method is deprecated since Symfony 2.4 and will be made private in 3.0.', E_USER_DEPRECATED);

        $this->setCurrentRequest($request);
    }

    private function setCurrentRequest(Request $request = null)
    {
        if (null !== $request && $this->request !== $request) {
            try {
                $this->context->fromRequest($request);
            } catch (\UnexpectedValueException $e) {
                throw new BadRequestHttpException($e->getMessage(), $e, $e->getCode());
            }
        }

        $this->request = $request;
    }

    public function onKernelFinishRequest(FinishRequestEvent $event)
    {
        if (null === $this->requestStack) {
            return; // removed when requestStack is required
        }

        $this->setCurrentRequest($this->requestStack->getParentRequest());
    }

    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        // initialize the context that is also used by the generator (assuming matcher and generator share the same context instance)
        // we call setCurrentRequest even if most of the time, it has already been done to keep compatibility
        // with frameworks which do not use the Symfony service container
        // when we have a RequestStack, no need to do it
        if (null !== $this->requestStack) {
            $this->setCurrentRequest($request);
        }

        if ($request->attributes->has('_controller')) {
            // routing is already done
            return;
        }

        // add attributes based on the request (routing)
        try {
            // matching a request is more powerful than matching a URL path + context, so try that first
            if ($this->matcher instanceof RequestMatcherInterface) {
                $parameters = $this->matcher->matchRequest($request);
            } else {
                $parameters = $this->matcher->match($request->getPathInfo());
            }

            if (null !== $this->logger) {
                $this->logger->info(sprintf('Matched route "%s".', isset($parameters['_route']) ? $parameters['_route'] : 'n/a'), array(
                    'route_parameters' => $parameters,
                    'request_uri' => $request->getUri(),
                ));
            }

            $request->attributes->add($parameters);
            unset($parameters['_route'], $parameters['_controller']);
            $request->attributes->set('_route_params', $parameters);
        } catch (ResourceNotFoundException $e) {
            $message = sprintf('No route found for "%s %s"', $request->getMethod(), $request->getPathInfo());

            if ($referer = $request->headers->get('referer')) {
                $message .= sprintf(' (from "%s")', $referer);
            }

            throw new NotFoundHttpException($message, $e);
        } catch (MethodNotAllowedException $e) {
            $message = sprintf('No route found for "%s %s": Method Not Allowed (Allow: %s)', $request->getMethod(), $request->getPathInfo(), implode(', ', $e->getAllowedMethods()));

            throw new MethodNotAllowedHttpException($e->getAllowedMethods(), $message, $e);
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            KernelEvents::REQUEST => array(array('onKernelRequest', 32)),
            KernelEvents::FINISH_REQUEST => array(array('onKernelFinishRequest', 0)),
        );
    }
}
