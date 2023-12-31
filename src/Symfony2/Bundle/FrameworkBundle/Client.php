<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\FrameworkBundle;

use Symfony2\Component\BrowserKit\CookieJar;
use Symfony2\Component\BrowserKit\History;
use Symfony2\Component\DependencyInjection\ContainerInterface;
use Symfony2\Component\HttpFoundation\Request;
use Symfony2\Component\HttpFoundation\Response;
use Symfony2\Component\HttpKernel\Client as BaseClient;
use Symfony2\Component\HttpKernel\KernelInterface;
use Symfony2\Component\HttpKernel\Profiler\Profile as HttpProfile;

/**
 * Client simulates a browser and makes requests to a Kernel object.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class Client extends BaseClient
{
    private $hasPerformedRequest = false;
    private $profiler = false;
    private $reboot = true;

    /**
     * {@inheritdoc}
     */
    public function __construct(KernelInterface $kernel, array $server = array(), History $history = null, CookieJar $cookieJar = null)
    {
        parent::__construct($kernel, $server, $history, $cookieJar);
    }

    /**
     * Returns the container.
     *
     * @return ContainerInterface|null Returns null when the Kernel has been shutdown or not started yet
     */
    public function getContainer()
    {
        return $this->kernel->getContainer();
    }

    /**
     * Returns the kernel.
     *
     * @return KernelInterface
     */
    public function getKernel()
    {
        return $this->kernel;
    }

    /**
     * Gets the profile associated with the current Response.
     *
     * @return HttpProfile|false A Profile instance
     */
    public function getProfile()
    {
        if (!$this->kernel->getContainer()->has('profiler')) {
            return false;
        }

        return $this->kernel->getContainer()->get('profiler')->loadProfileFromResponse($this->response);
    }

    /**
     * Enables the profiler for the very next request.
     *
     * If the profiler is not enabled, the call to this method does nothing.
     */
    public function enableProfiler()
    {
        if ($this->kernel->getContainer()->has('profiler')) {
            $this->profiler = true;
        }
    }

    /**
     * Disables kernel reboot between requests.
     *
     * By default, the Client reboots the Kernel for each request. This method
     * allows to keep the same kernel across requests.
     */
    public function disableReboot()
    {
        $this->reboot = false;
    }

    /**
     * Enables kernel reboot between requests.
     */
    public function enableReboot()
    {
        $this->reboot = true;
    }

    /**
     * {@inheritdoc}
     *
     * @param Request $request A Request instance
     *
     * @return Response A Response instance
     */
    protected function doRequest($request)
    {
        // avoid shutting down the Kernel if no request has been performed yet
        // WebTestCase::createClient() boots the Kernel but do not handle a request
        if ($this->hasPerformedRequest && $this->reboot) {
            $this->kernel->shutdown();
        } else {
            $this->hasPerformedRequest = true;
        }

        if ($this->profiler) {
            $this->profiler = false;

            $this->kernel->boot();
            $this->kernel->getContainer()->get('profiler')->enable();
        }

        return parent::doRequest($request);
    }

    /**
     * {@inheritdoc}
     *
     * @param Request $request A Request instance
     *
     * @return Response A Response instance
     */
    protected function doRequestInProcess($request)
    {
        $response = parent::doRequestInProcess($request);

        $this->profiler = false;

        return $response;
    }

    /**
     * Returns the script to execute when the request must be insulated.
     *
     * It assumes that the autoloader is named 'autoload.php' and that it is
     * stored in the same directory as the kernel (this is the case for the
     * Symfony Standard Edition). If this is not your case, create your own
     * client and override this method.
     *
     * @param Request $request A Request instance
     *
     * @return string The script content
     */
    protected function getScript($request)
    {
        $kernel = var_export(serialize($this->kernel), true);
        $request = var_export(serialize($request), true);

        $r = new \ReflectionObject($this->kernel);

        $autoloader = \dirname($r->getFileName()).'/autoload.php';
        if (is_file($autoloader)) {
            $autoloader = var_export($autoloader, true);
        } else {
            $autoloader = 'false';
        }

        $path = var_export($r->getFileName(), true);

        $profilerCode = '';
        if ($this->profiler) {
            $profilerCode = '$kernel->getContainer()->get(\'profiler\')->enable();';
        }

        $errorReporting = error_reporting();

        $code = <<<EOF
<?php

error_reporting($errorReporting);

if ($autoloader) {
    require_once $autoloader;
}
require_once $path;

\$kernel = unserialize($kernel);
\$kernel->boot();
$profilerCode

\$request = unserialize($request);
EOF;

        return $code.$this->getHandleScript();
    }
}
