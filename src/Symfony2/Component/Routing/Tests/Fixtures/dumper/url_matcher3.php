<?php

use Symfony2\Component\Routing\Exception\MethodNotAllowedException;
use Symfony2\Component\Routing\Exception\ResourceNotFoundException;
use Symfony2\Component\Routing\RequestContext;

/**
 * This class has been auto-generated
 * by the Symfony Routing Component.
 */
class ProjectUrlMatcher extends Symfony2\Component\Routing\Matcher\UrlMatcher
{
    public function __construct(RequestContext $context)
    {
        $this->context = $context;
    }

    public function match($rawPathinfo)
    {
        $allow = array();
        $pathinfo = rawurldecode($rawPathinfo);
        $context = $this->context;
        $request = $this->request ?: $this->createRequest($pathinfo);

        if (0 === strpos($pathinfo, '/rootprefix')) {
            // static
            if ('/rootprefix/test' === $pathinfo) {
                return array('_route' => 'static');
            }

            // dynamic
            if (preg_match('#^/rootprefix/(?P<var>[^/]++)$#sD', $pathinfo, $matches)) {
                return $this->mergeDefaults(array_replace($matches, array('_route' => 'dynamic')), array ());
            }

        }

        // with-condition
        if ('/with-condition' === $pathinfo && ($context->getMethod() == "GET")) {
            return array('_route' => 'with-condition');
        }

        throw 0 < count($allow) ? new MethodNotAllowedException(array_unique($allow)) : new ResourceNotFoundException();
    }
}
