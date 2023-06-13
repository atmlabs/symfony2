<?php
namespace Symfony2\Bundle\DoctrineCacheBundle;

use Symfony2\Component\Console\Application;
use Symfony2\Component\HttpKernel\Bundle\Bundle;

/**
 * Symfony Bundle for Doctrine Cache
 *
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author Fabio B. Silva <fabio.bat.silva@gmail.com>
 */
class DoctrineCacheBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function registerCommands(Application $application)
    {
    }
}
