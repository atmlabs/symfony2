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

use Symfony2\Component\EventDispatcher\EventSubscriberInterface;
use Symfony2\Component\HttpFoundation\RequestMatcherInterface;
use Symfony2\Component\HttpFoundation\RequestStack;
use Symfony2\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony2\Component\HttpKernel\Event\GetResponseEvent;
use Symfony2\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony2\Component\HttpKernel\Event\PostResponseEvent;
use Symfony2\Component\HttpKernel\KernelEvents;
use Symfony2\Component\HttpKernel\Profiler\Profiler;

/**
 * ProfilerListener collects data for the current request by listening to the kernel events.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class ProfilerListener implements EventSubscriberInterface
{
    protected $profiler;
    protected $matcher;
    protected $onlyException;
    protected $onlyMasterRequests;
    protected $exception;
    protected $requests = array();
    protected $profiles;
    protected $requestStack;
    protected $parents;

    /**
     * @param Profiler                     $profiler           A Profiler instance
     * @param RequestStack                 $requestStack       A RequestStack instance
     * @param RequestMatcherInterface|null $matcher            A RequestMatcher instance
     * @param bool                         $onlyException      True if the profiler only collects data when an exception occurs, false otherwise
     * @param bool                         $onlyMasterRequests True if the profiler only collects data when the request is a master request, false otherwise
     */
    public function __construct(Profiler $profiler, $requestStack = null, $matcher = null, $onlyException = false, $onlyMasterRequests = false)
    {
        if ($requestStack instanceof RequestMatcherInterface || (null !== $matcher && !$matcher instanceof RequestMatcherInterface) || $onlyMasterRequests instanceof RequestStack) {
            $tmp = $onlyMasterRequests;
            $onlyMasterRequests = $onlyException;
            $onlyException = $matcher;
            $matcher = $requestStack;
            $requestStack = \func_num_args() < 5 ? null : $tmp;

            @trigger_error('The '.__METHOD__.' method now requires a RequestStack to be given as second argument as '.__CLASS__.'::onKernelRequest method will be removed in 3.0.', E_USER_DEPRECATED);
        } elseif (!$requestStack instanceof RequestStack) {
            @trigger_error('The '.__METHOD__.' method now requires a RequestStack instance as '.__CLASS__.'::onKernelRequest method will be removed in 3.0.', E_USER_DEPRECATED);
        }

        if (null !== $requestStack && !$requestStack instanceof RequestStack) {
            throw new \InvalidArgumentException('RequestStack instance expected.');
        }
        if (null !== $matcher && !$matcher instanceof RequestMatcherInterface) {
            throw new \InvalidArgumentException('Matcher must implement RequestMatcherInterface.');
        }

        $this->profiler = $profiler;
        $this->matcher = $matcher;
        $this->onlyException = (bool) $onlyException;
        $this->onlyMasterRequests = (bool) $onlyMasterRequests;
        $this->profiles = new \SplObjectStorage();
        $this->parents = new \SplObjectStorage();
        $this->requestStack = $requestStack;
    }

    /**
     * Handles the onKernelException event.
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        if ($this->onlyMasterRequests && !$event->isMasterRequest()) {
            return;
        }

        $this->exception = $event->getException();
    }

    /**
     * @deprecated since version 2.4, to be removed in 3.0.
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (null === $this->requestStack) {
            $this->requests[] = $event->getRequest();
        }
    }

    /**
     * Handles the onKernelResponse event.
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $master = $event->isMasterRequest();
        if ($this->onlyMasterRequests && !$master) {
            return;
        }

        if ($this->onlyException && null === $this->exception) {
            return;
        }

        $request = $event->getRequest();
        $exception = $this->exception;
        $this->exception = null;

        if (null !== $this->matcher && !$this->matcher->matches($request)) {
            return;
        }

        if (!$profile = $this->profiler->collect($request, $event->getResponse(), $exception)) {
            return;
        }

        $this->profiles[$request] = $profile;

        if (null !== $this->requestStack) {
            $this->parents[$request] = $this->requestStack->getParentRequest();
        } elseif (!$master) {
            // to be removed when requestStack is required
            array_pop($this->requests);

            $this->parents[$request] = end($this->requests);
        }
    }

    public function onKernelTerminate(PostResponseEvent $event)
    {
        // attach children to parents
        foreach ($this->profiles as $request) {
            // isset call should be removed when requestStack is required
            if (isset($this->parents[$request]) && null !== $parentRequest = $this->parents[$request]) {
                if (isset($this->profiles[$parentRequest])) {
                    $this->profiles[$parentRequest]->addChild($this->profiles[$request]);
                }
            }
        }

        // save profiles
        foreach ($this->profiles as $request) {
            $this->profiler->saveProfile($this->profiles[$request]);
        }

        $this->profiles = new \SplObjectStorage();
        $this->parents = new \SplObjectStorage();
        $this->requests = array();
    }

    public static function getSubscribedEvents()
    {
        return array(
            // kernel.request must be registered as early as possible to not break
            // when an exception is thrown in any other kernel.request listener
            KernelEvents::REQUEST => array('onKernelRequest', 1024),
            KernelEvents::RESPONSE => array('onKernelResponse', -100),
            KernelEvents::EXCEPTION => 'onKernelException',
            KernelEvents::TERMINATE => array('onKernelTerminate', -1024),
        );
    }
}
