<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sensio\Bundle\DistributionBundle\Composer;

use Symfony2\Component\ClassLoader\ClassCollectionLoader;
use Symfony2\Component\Filesystem\Filesystem;
use Symfony2\Component\Process\Process;
use Symfony2\Component\Process\PhpExecutableFinder;
use Composer\Script\Event;

/**
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class ScriptHandler
{
    /**
     * Composer variables are declared static so that an event could update
     * a composer.json and set new options, making them immediately available
     * to forthcoming listeners.
     */
    protected static $options = array(
        'symfony-app-dir' => 'app',
        'symfony-web-dir' => 'web',
        'symfony-assets-install' => 'hard',
        'symfony-cache-warmup' => false,
    );

    /**
     * Asks if the new directory structure should be used, installs the structure if needed.
     *
     * @param Event $event
     */
    public static function defineDirectoryStructure(Event $event)
    {
        $options = static::getOptions($event);

        if (!getenv('SENSIOLABS_ENABLE_NEW_DIRECTORY_STRUCTURE') || !$event->getIO()->askConfirmation('Would you like to use Symfony 3 directory structure? [y/N] ', false)) {
            return;
        }

        $rootDir = getcwd();
        $appDir = $options['symfony-app-dir'];
        $webDir = $options['symfony-web-dir'];
        $binDir = static::$options['symfony-bin-dir'] = 'bin';
        $varDir = static::$options['symfony-var-dir'] = 'var';

        static::updateDirectoryStructure($event, $rootDir, $appDir, $binDir, $varDir, $webDir);
    }

    /**
     * Builds the bootstrap file.
     *
     * The bootstrap file contains PHP file that are always needed by the application.
     * It speeds up the application bootstrapping.
     *
     * @param Event $event
     */
    public static function buildBootstrap(Event $event)
    {
        $options = static::getOptions($event);
        $bootstrapDir = $autoloadDir = $options['symfony-app-dir'];

        if (static::useNewDirectoryStructure($options)) {
            $bootstrapDir = $options['symfony-var-dir'];
            if (!static::hasDirectory($event, 'symfony-var-dir', $bootstrapDir, 'build bootstrap file')) {
                return;
            }
        }
        if (!static::hasDirectory($event, 'symfony-app-dir', $autoloadDir, 'build bootstrap file')) {
            return;
        }

        static::executeBuildBootstrap($event, $bootstrapDir, $autoloadDir, $options['process-timeout']);
    }

    protected static function hasDirectory(Event $event, $configName, $path, $actionName)
    {
        if (!is_dir($path)) {
            $event->getIO()->write(sprintf('The %s (%s) specified in composer.json was not found in %s, can not %s.', $configName, $path, getcwd(), $actionName));

            return false;
        }

        return true;
    }

    /**
     * Sets up deployment target specific features.
     * Could be custom web server configs, boot command files etc.
     *
     * @param Event $event
     */
    public static function prepareDeploymentTarget(Event $event)
    {
        static::prepareDeploymentTargetHeroku($event);
    }

    protected static function prepareDeploymentTargetHeroku(Event $event)
    {
        $options = static::getOptions($event);
        if (($stack = getenv('STACK')) && ('cedar' == $stack || 'cedar-14' == $stack)) {
            $fs = new Filesystem();
            if (!$fs->exists('Procfile')) {
                $event->getIO()->write('Heroku deploy detected; creating default Procfile for "web" dyno');
                $fs->dumpFile('Procfile', sprintf('web: $(composer config bin-dir)/heroku-php-apache2 %s/', $options['symfony-web-dir']));
            }
        }
    }

    /**
     * Clears the Symfony cache.
     *
     * @param Event $event
     */
    public static function clearCache(Event $event)
    {
        $options = static::getOptions($event);
        $consoleDir = static::getConsoleDir($event, 'clear the cache');

        if (null === $consoleDir) {
            return;
        }

        $warmup = '';
        if (!$options['symfony-cache-warmup']) {
            $warmup = ' --no-warmup';
        }

        static::executeCommand($event, $consoleDir, 'cache:clear'.$warmup, $options['process-timeout']);
    }

    /**
     * Installs the assets under the web root directory.
     *
     * For better interoperability, assets are copied instead of symlinked by default.
     *
     * Even if symlinks work on Windows, this is only true on Windows Vista and later,
     * but then, only when running the console with admin rights or when disabling the
     * strict user permission checks (which can be done on Windows 7 but not on Windows
     * Vista).
     *
     * @param Event $event
     */
    public static function installAssets(Event $event)
    {
        $options = static::getOptions($event);
        $consoleDir = static::getConsoleDir($event, 'install assets');

        if (null === $consoleDir) {
            return;
        }

        $webDir = $options['symfony-web-dir'];

        $symlink = '';
        if ('symlink' == $options['symfony-assets-install']) {
            $symlink = '--symlink ';
        } elseif ('relative' == $options['symfony-assets-install']) {
            $symlink = '--symlink --relative ';
        }

        if (!static::hasDirectory($event, 'symfony-web-dir', $webDir, 'install assets')) {
            return;
        }

        static::executeCommand($event, $consoleDir, 'assets:install '.$symlink.escapeshellarg($webDir), $options['process-timeout']);
    }

    /**
     * Updated the requirements file.
     *
     * @param Event $event
     */
    public static function installRequirementsFile(Event $event)
    {
        $options = static::getOptions($event);
        $appDir = $options['symfony-app-dir'];
        $fs = new Filesystem();

        $newDirectoryStructure = static::useNewDirectoryStructure($options);

        if (!$newDirectoryStructure) {
            if (!static::hasDirectory($event, 'symfony-app-dir', $appDir, 'install the requirements files')) {
                return;
            }
            $fs->copy(__DIR__.'/../Resources/skeleton/app/SymfonyRequirements.php', $appDir.'/SymfonyRequirements.php', true);
            $fs->copy(__DIR__.'/../Resources/skeleton/app/check.php', $appDir.'/check.php', true);
        } else {
            $binDir = $options['symfony-bin-dir'];
            $varDir = $options['symfony-var-dir'];
            if (!static::hasDirectory($event, 'symfony-var-dir', $varDir, 'install the requirements files')) {
                return;
            }
            if (!static::hasDirectory($event, 'symfony-bin-dir', $binDir, 'install the requirements files')) {
                return;
            }
            $fs->copy(__DIR__.'/../Resources/skeleton/app/SymfonyRequirements.php', $varDir.'/SymfonyRequirements.php', true);
            $fs->copy(__DIR__.'/../Resources/skeleton/app/check.php', $binDir.'/symfony_requirements', true);
            $fs->remove(array($appDir.'/check.php', $appDir.'/SymfonyRequirements.php', true));

            $fs->dumpFile($binDir.'/symfony_requirements', '#!/usr/bin/env php'.PHP_EOL.str_replace(".'/SymfonyRequirements.php'", ".'/".$fs->makePathRelative(realpath($varDir), realpath($binDir))."SymfonyRequirements.php'", file_get_contents($binDir.'/symfony_requirements')));
            $fs->chmod($binDir.'/symfony_requirements', 0755);
        }

        $webDir = $options['symfony-web-dir'];

        // if the user has already removed the config.php file, do nothing
        // as the file must be removed for production use
        if ($fs->exists($webDir.'/config.php')) {
            $requiredDir = $newDirectoryStructure ? $varDir : $appDir;

            $fs->dumpFile($webDir.'/config.php', str_replace('/../app/SymfonyRequirements.php', '/'.$fs->makePathRelative(realpath($requiredDir), realpath($webDir)).'SymfonyRequirements.php', file_get_contents(__DIR__.'/../Resources/skeleton/web/config.php')));
        }
    }

    public static function removeSymfonyStandardFiles(Event $event)
    {
        $options = static::getOptions($event);
        $appDir = $options['symfony-app-dir'];

        if (!is_dir($appDir)) {
            return;
        }

        if (!is_dir($appDir.'/SymfonyStandard')) {
            return;
        }

        $fs = new Filesystem();
        $fs->remove($appDir.'/SymfonyStandard');
    }

    public static function doBuildBootstrap($bootstrapDir, $autoloadDir = null, $useNewDirectoryStructure = false)
    {
        $file = $bootstrapDir.'/bootstrap.php.cache';
        if (file_exists($file)) {
            unlink($file);
        }

        $classes = array(
            'Symfony2\\Component\\HttpFoundation\\ParameterBag',
            'Symfony2\\Component\\HttpFoundation\\HeaderBag',
            'Symfony2\\Component\\HttpFoundation\\FileBag',
            'Symfony2\\Component\\HttpFoundation\\ServerBag',
            'Symfony2\\Component\\HttpFoundation\\Request',
            'Symfony2\\Component\\HttpFoundation\\Response',
            'Symfony2\\Component\\HttpFoundation\\ResponseHeaderBag',

            'Symfony2\\Component\\DependencyInjection\\ContainerAwareInterface',
            // Cannot be included because annotations will parse the big compiled class file
            //'Symfony2\\Component\\DependencyInjection\\ContainerAware',
            'Symfony2\\Component\\DependencyInjection\\Container',
            'Symfony2\\Component\\HttpKernel\\Kernel',
            'Symfony2\\Component\\ClassLoader\\ClassCollectionLoader',
            'Symfony2\\Component\\HttpKernel\\Bundle\\Bundle',
            'Symfony2\\Component\\Config\\ConfigCache',
            // cannot be included as commands are discovered based on the path to this class via Reflection
            //'Symfony2\\Bundle\\FrameworkBundle\\FrameworkBundle',
        );

        // introspect the autoloader to get the right file
        // we cannot use class_exist() here as it would load the class
        // which won't be included into the cache then.
        // we know that composer autoloader is first (see bin/build_bootstrap.php)
        $autoloaders = spl_autoload_functions();
        if (is_array($autoloaders[0]) && method_exists($autoloaders[0][0], 'findFile') && $autoloaders[0][0]->findFile('Symfony2\\Bundle\\FrameworkBundle\\HttpKernel')) {
            $classes[] = 'Symfony2\\Bundle\\FrameworkBundle\\HttpKernel';
        } elseif (is_array($autoloaders[0]) && method_exists($autoloaders[0][0], 'findFile') && $autoloaders[0][0]->findFile('Symfony2\\Component\\HttpKernel\\DependencyInjection\\ContainerAwareHttpKernel')) {
            $classes[] = 'Symfony2\\Component\\HttpKernel\\DependencyInjection\\ContainerAwareHttpKernel';
        } else {
            $classes[] = 'Symfony2\\Component\\HttpKernel\\HttpKernel';
        }

        ClassCollectionLoader::load($classes, dirname($file), basename($file, '.php.cache'), false, false, '.php.cache');

        $fs = new Filesystem();
        $bootstrapContent = substr(file_get_contents($file), 5);

        if ($useNewDirectoryStructure) {
            $cacheDir = $fs->makePathRelative(realpath($bootstrapDir), realpath($autoloadDir));
            $bootstrapContent = str_replace(array("return \$this->rootDir.'/logs", "return \$this->rootDir.'/cache"), array("return \$this->rootDir.'/".$cacheDir.'logs', "return \$this->rootDir.'/".$cacheDir.'cache'), $bootstrapContent);
        }

        if ($autoloadDir) {
            $fs = new Filesystem();
            $autoloadDir = $fs->makePathRelative(realpath($autoloadDir), realpath($bootstrapDir));
        }

        file_put_contents($file, sprintf(<<<'EOF'
<?php

namespace {
    error_reporting(error_reporting() & ~E_USER_DEPRECATED);
    $loader = require_once __DIR__.'/%sautoload.php';
}

%s

namespace { return $loader; }

EOF
            , $autoloadDir, $bootstrapContent));
    }

    protected static function executeCommand(Event $event, $consoleDir, $cmd, $timeout = 300)
    {
        $php = escapeshellarg(static::getPhp(false));
        $phpArgs = implode(' ', array_map('escapeshellarg', static::getPhpArguments()));
        $console = escapeshellarg($consoleDir.'/console');
        if ($event->getIO()->isDecorated()) {
            $console .= ' --ansi';
        }

        $process = new Process($php.($phpArgs ? ' '.$phpArgs : '').' '.$console.' '.$cmd, null, null, null, $timeout);
        $process->run(function ($type, $buffer) use ($event) { $event->getIO()->write($buffer, false); });
        if (!$process->isSuccessful()) {
            throw new \RuntimeException(sprintf("An error occurred when executing the \"%s\" command:\n\n%s\n\n%s.", escapeshellarg($cmd), $process->getOutput(), $process->getErrorOutput()));
        }
    }

    protected static function executeBuildBootstrap(Event $event, $bootstrapDir, $autoloadDir, $timeout = 300)
    {
        $php = escapeshellarg(static::getPhp(false));
        $phpArgs = implode(' ', array_map('escapeshellarg', static::getPhpArguments()));
        $cmd = escapeshellarg(__DIR__.'/../Resources/bin/build_bootstrap.php');
        $bootstrapDir = escapeshellarg($bootstrapDir);
        $autoloadDir = escapeshellarg($autoloadDir);
        $useNewDirectoryStructure = '';
        if (static::useNewDirectoryStructure(static::getOptions($event))) {
            $useNewDirectoryStructure = escapeshellarg('--use-new-directory-structure');
        }

        $process = new Process($php.($phpArgs ? ' '.$phpArgs : '').' '.$cmd.' '.$bootstrapDir.' '.$autoloadDir.' '.$useNewDirectoryStructure, getcwd(), null, null, $timeout);
        $process->run(function ($type, $buffer) use ($event) { $event->getIO()->write($buffer, false); });
        if (!$process->isSuccessful()) {
            throw new \RuntimeException('An error occurred when generating the bootstrap file.');
        }
    }

    protected static function updateDirectoryStructure(Event $event, $rootDir, $appDir, $binDir, $varDir, $webDir)
    {
        $event->getIO()->write('Updating Symfony directory structure...');

        $fs = new Filesystem();

        $fs->mkdir(array($binDir, $varDir));

        foreach (array(
            $appDir.'/console' => $binDir.'/console',
            $appDir.'/phpunit.xml.dist' => $rootDir.'/phpunit.xml.dist',
        ) as $source => $target) {
            $fs->rename($source, $target, true);
        }

        foreach (array('/logs', '/cache') as $dir) {
            $fs->rename($appDir.$dir, $varDir.$dir);
        }

        $gitignore = <<<EOF
/web/bundles/
/app/config/parameters.yml
/var/bootstrap.php.cache
/var/SymfonyRequirements.php
/var/cache/*
/var/logs/*
!var/cache/.gitkeep
!var/logs/.gitkeep
/build/
/vendor/
/bin/*
!bin/console
!bin/symfony_requirements
/composer.phar
EOF;
        $phpunitKernelBefore = <<<EOF
    <!--
    <php>
        <server name="KERNEL_DIR" value="/path/to/your/app/" />
    </php>
    -->
EOF;
        $phpunitKernelAfter = <<<EOF
    <php>
        <server name="KERNEL_DIR" value="$appDir/" />
    </php>
EOF;
        $phpunit = str_replace(array('<directory>../src', '"bootstrap.php.cache"', $phpunitKernelBefore), array('<directory>src', '"'.$varDir.'/bootstrap.php.cache"', $phpunitKernelAfter), file_get_contents($rootDir.'/phpunit.xml.dist'));
        $composer = str_replace('"symfony-app-dir": "app",', "\"symfony-app-dir\": \"app\",\n        \"symfony-bin-dir\": \"bin\",\n        \"symfony-var-dir\": \"var\",", file_get_contents($rootDir.'/composer.json'));

        $fs->dumpFile($webDir.'/app.php', str_replace($appDir.'/bootstrap.php.cache', $varDir.'/bootstrap.php.cache', file_get_contents($webDir.'/app.php')));
        $fs->dumpFile($webDir.'/app_dev.php', str_replace($appDir.'/bootstrap.php.cache', $varDir.'/bootstrap.php.cache', file_get_contents($webDir.'/app_dev.php')));
        $fs->dumpFile($binDir.'/console', str_replace(array(".'/bootstrap.php.cache'", ".'/AppKernel.php'"), array(".'/".$fs->makePathRelative(realpath($varDir), realpath($binDir))."bootstrap.php.cache'", ".'/".$fs->makePathRelative(realpath($appDir), realpath($binDir))."AppKernel.php'"), file_get_contents($binDir.'/console')));
        $fs->dumpFile($rootDir.'/phpunit.xml.dist', $phpunit);
        $fs->dumpFile($rootDir.'/composer.json', $composer);

        $fs->dumpFile($rootDir.'/.gitignore', $gitignore);

        $fs->chmod($binDir.'/console', 0755);
    }

    protected static function getOptions(Event $event)
    {
        $options = array_merge(static::$options, $event->getComposer()->getPackage()->getExtra());

        $options['symfony-assets-install'] = getenv('SYMFONY_ASSETS_INSTALL') ?: $options['symfony-assets-install'];
        $options['symfony-cache-warmup'] = getenv('SYMFONY_CACHE_WARMUP') ?: $options['symfony-cache-warmup'];

        $options['process-timeout'] = $event->getComposer()->getConfig()->get('process-timeout');

        return $options;
    }

    protected static function getPhp($includeArgs = true)
    {
        $phpFinder = new PhpExecutableFinder();
        if (!$phpPath = $phpFinder->find($includeArgs)) {
            throw new \RuntimeException('The php executable could not be found, add it to your PATH environment variable and try again');
        }

        return $phpPath;
    }

    protected static function getPhpArguments()
    {
        $ini = null;
        $arguments = array();

        $phpFinder = new PhpExecutableFinder();
        if (method_exists($phpFinder, 'findArguments')) {
            $arguments = $phpFinder->findArguments();
        }

        if ($env = (string) (getenv('COMPOSER_ORIGINAL_INIS'))) {
            $paths = explode(PATH_SEPARATOR, $env);
            $ini = array_shift($paths);
        } else {
            $ini = php_ini_loaded_file();
        }

        if ($ini) {
            $arguments[] = '--php-ini='.$ini;
        }

        return $arguments;
    }

    /**
     * Returns a relative path to the directory that contains the `console` command.
     *
     * @param Event  $event      The command event
     * @param string $actionName The name of the action
     *
     * @return string|null The path to the console directory, null if not found.
     */
    protected static function getConsoleDir(Event $event, $actionName)
    {
        $options = static::getOptions($event);

        if (static::useNewDirectoryStructure($options)) {
            if (!static::hasDirectory($event, 'symfony-bin-dir', $options['symfony-bin-dir'], $actionName)) {
                return;
            }

            return $options['symfony-bin-dir'];
        }

        if (!static::hasDirectory($event, 'symfony-app-dir', $options['symfony-app-dir'], 'execute command')) {
            return;
        }

        return $options['symfony-app-dir'];
    }

    /**
     * Returns true if the new directory structure is used.
     *
     * @param array $options Composer options
     *
     * @return bool
     */
    protected static function useNewDirectoryStructure(array $options)
    {
        return isset($options['symfony-var-dir']) && is_dir($options['symfony-var-dir']);
    }
}
