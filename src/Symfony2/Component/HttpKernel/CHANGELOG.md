CHANGELOG
=========

2.8.0
-----

 * deprecated `Profiler::import` and `Profiler::export`

2.7.0
-----

 * added the HTTP status code to profiles

2.6.0
-----

 * deprecated `Symfony2\Component\HttpKernel\EventListener\ErrorsLoggerListener`, use `Symfony2\Component\HttpKernel\EventListener\DebugHandlersListener` instead
 * deprecated unused method `Symfony2\Component\HttpKernel\Kernel::isClassInActiveBundle` and `Symfony2\Component\HttpKernel\KernelInterface::isClassInActiveBundle`

2.5.0
-----

 * deprecated `Symfony2\Component\HttpKernel\DependencyInjection\RegisterListenersPass`, use `Symfony2\Component\EventDispatcher\DependencyInjection\RegisterListenersPass` instead

2.4.0
-----

 * added event listeners for the session
 * added the KernelEvents::FINISH_REQUEST event

2.3.0
-----

 * [BC BREAK] renamed `Symfony2\Component\HttpKernel\EventListener\DeprecationLoggerListener` to `Symfony2\Component\HttpKernel\EventListener\ErrorsLoggerListener` and changed its constructor
 * deprecated `Symfony2\Component\HttpKernel\Debug\ErrorHandler`, `Symfony2\Component\HttpKernel\Debug\ExceptionHandler`,
   `Symfony2\Component\HttpKernel\Exception\FatalErrorException` and `Symfony2\Component\HttpKernel\Exception\FlattenException`
 * deprecated `Symfony2\Component\HttpKernel\Kernel::init()``
 * added the possibility to specify an id an extra attributes to hinclude tags
 * added the collect of data if a controller is a Closure in the Request collector
 * pass exceptions from the ExceptionListener to the logger using the logging context to allow for more
   detailed messages

2.2.0
-----

 * [BC BREAK] the path info for sub-request is now always _fragment (or whatever you configured instead of the default)
 * added Symfony2\Component\HttpKernel\EventListener\FragmentListener
 * added Symfony2\Component\HttpKernel\UriSigner
 * added Symfony2\Component\HttpKernel\FragmentRenderer and rendering strategies (in Symfony2\Component\HttpKernel\Fragment\FragmentRendererInterface)
 * added Symfony2\Component\HttpKernel\DependencyInjection\ContainerAwareHttpKernel
 * added ControllerReference to create reference of Controllers (used in the FragmentRenderer class)
 * [BC BREAK] renamed TimeDataCollector::getTotalTime() to
   TimeDataCollector::getDuration()
 * updated the MemoryDataCollector to include the memory used in the
   kernel.terminate event listeners
 * moved the Stopwatch classes to a new component
 * added TraceableControllerResolver
 * added TraceableEventDispatcher (removed ContainerAwareTraceableEventDispatcher)
 * added support for WinCache opcode cache in ConfigDataCollector

2.1.0
-----

 * [BC BREAK] the charset is now configured via the Kernel::getCharset() method
 * [BC BREAK] the current locale for the user is not stored anymore in the session
 * added the HTTP method to the profiler storage
 * updated all listeners to implement EventSubscriberInterface
 * added TimeDataCollector
 * added ContainerAwareTraceableEventDispatcher
 * moved TraceableEventDispatcherInterface to the EventDispatcher component
 * added RouterListener, LocaleListener, and StreamedResponseListener
 * added CacheClearerInterface (and ChainCacheClearer)
 * added a kernel.terminate event (via TerminableInterface and PostResponseEvent)
 * added a Stopwatch class
 * added WarmableInterface
 * improved extensibility between bundles
 * added profiler storages for Memcache(d), File-based, MongoDB, Redis
 * moved Filesystem class to its own component
