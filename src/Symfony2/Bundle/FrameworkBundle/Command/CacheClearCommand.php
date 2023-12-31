<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\FrameworkBundle\Command;

use Symfony2\Component\Console\Input\InputInterface;
use Symfony2\Component\Console\Input\InputOption;
use Symfony2\Component\Console\Output\OutputInterface;
use Symfony2\Component\Console\Style\SymfonyStyle;
use Symfony2\Component\Finder\Finder;
use Symfony2\Component\HttpKernel\KernelInterface;

/**
 * Clear and Warmup the cache.
 *
 * @author Francis Besset <francis.besset@gmail.com>
 * @author Fabien Potencier <fabien@symfony.com>
 */
class CacheClearCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('cache:clear')
            ->setDefinition(array(
                new InputOption('no-warmup', '', InputOption::VALUE_NONE, 'Do not warm up the cache'),
                new InputOption('no-optional-warmers', '', InputOption::VALUE_NONE, 'Skip optional cache warmers (faster)'),
            ))
            ->setDescription('Clears the cache')
            ->setHelp(<<<'EOF'
The <info>%command.name%</info> command clears the application cache for a given environment
and debug mode:

  <info>php %command.full_name% --env=dev</info>
  <info>php %command.full_name% --env=prod --no-debug</info>
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $outputIsVerbose = $output->isVerbose();
        $io = new SymfonyStyle($input, $output);

        $realCacheDir = $this->getContainer()->getParameter('kernel.cache_dir');
        // the old cache dir name must not be longer than the real one to avoid exceeding
        // the maximum length of a directory or file path within it (esp. Windows MAX_PATH)
        $oldCacheDir = substr($realCacheDir, 0, -1).('~' === substr($realCacheDir, -1) ? '+' : '~');
        $filesystem = $this->getContainer()->get('filesystem');

        if (!is_writable($realCacheDir)) {
            throw new \RuntimeException(sprintf('Unable to write in the "%s" directory', $realCacheDir));
        }

        if ($filesystem->exists($oldCacheDir)) {
            $filesystem->remove($oldCacheDir);
        }

        $kernel = $this->getContainer()->get('kernel');
        $io->comment(sprintf('Clearing the cache for the <info>%s</info> environment with debug <info>%s</info>', $kernel->getEnvironment(), var_export($kernel->isDebug(), true)));
        $this->getContainer()->get('cache_clearer')->clear($realCacheDir);

        if ($input->getOption('no-warmup')) {
            $filesystem->rename($realCacheDir, $oldCacheDir);
        } else {
            // the warmup cache dir name must have the same length than the real one
            // to avoid the many problems in serialized resources files
            $realCacheDir = realpath($realCacheDir);
            $warmupDir = substr($realCacheDir, 0, -1).('_' === substr($realCacheDir, -1) ? '-' : '_');

            if ($filesystem->exists($warmupDir)) {
                if ($outputIsVerbose) {
                    $io->comment('Clearing outdated warmup directory...');
                }
                $filesystem->remove($warmupDir);
            }

            if ($outputIsVerbose) {
                $io->comment('Warming up cache...');
            }
            $this->warmup($warmupDir, $realCacheDir, !$input->getOption('no-optional-warmers'));

            $filesystem->rename($realCacheDir, $oldCacheDir);
            if ('\\' === \DIRECTORY_SEPARATOR) {
                sleep(1);  // workaround for Windows PHP rename bug
            }
            $filesystem->rename($warmupDir, $realCacheDir);
        }

        if ($outputIsVerbose) {
            $io->comment('Removing old cache directory...');
        }

        $filesystem->remove($oldCacheDir);

        if ($outputIsVerbose) {
            $io->comment('Finished');
        }

        $io->success(sprintf('Cache for the "%s" environment (debug=%s) was successfully cleared.', $kernel->getEnvironment(), var_export($kernel->isDebug(), true)));
    }

    /**
     * @param string $warmupDir
     * @param string $realCacheDir
     * @param bool   $enableOptionalWarmers
     */
    protected function warmup($warmupDir, $realCacheDir, $enableOptionalWarmers = true)
    {
        // create a temporary kernel
        $realKernel = $this->getContainer()->get('kernel');
        $realKernelClass = \get_class($realKernel);
        $namespace = '';
        if (false !== $pos = strrpos($realKernelClass, '\\')) {
            $namespace = substr($realKernelClass, 0, $pos);
            $realKernelClass = substr($realKernelClass, $pos + 1);
        }
        $tempKernel = $this->getTempKernel($realKernel, $namespace, $realKernelClass, $warmupDir);
        $tempKernel->boot();

        $tempKernelReflection = new \ReflectionObject($tempKernel);
        $tempKernelFile = $tempKernelReflection->getFileName();

        // warmup temporary dir
        $warmer = $tempKernel->getContainer()->get('cache_warmer');
        if ($enableOptionalWarmers) {
            $warmer->enableOptionalWarmers();
        }
        $warmer->warmUp($warmupDir);

        // fix references to the Kernel in .meta files
        $safeTempKernel = str_replace('\\', '\\\\', \get_class($tempKernel));
        $realKernelFQN = \get_class($realKernel);

        foreach (Finder::create()->files()->depth('<3')->name('*.meta')->in($warmupDir) as $file) {
            file_put_contents($file, preg_replace(
                '/(C\:\d+\:)"'.$safeTempKernel.'"/',
                sprintf('$1"%s"', $realKernelFQN),
                file_get_contents($file)
            ));
        }

        // fix references to cached files with the real cache directory name
        $search = array($warmupDir, str_replace('\\', '\\\\', $warmupDir));
        $replace = str_replace('\\', '/', $realCacheDir);
        foreach (Finder::create()->files()->in($warmupDir) as $file) {
            $content = str_replace($search, $replace, file_get_contents($file), $count);
            if ($count) {
                file_put_contents($file, $content);
            }
        }

        // fix references to container's class
        $tempContainerClass = \get_class($tempKernel->getContainer());
        $realContainerClass = \get_class($realKernel->getContainer());
        foreach (Finder::create()->files()->depth('<2')->name($tempContainerClass.'*')->in($warmupDir) as $file) {
            $content = str_replace($tempContainerClass, $realContainerClass, file_get_contents($file));
            file_put_contents($file, $content);
            rename($file, str_replace(\DIRECTORY_SEPARATOR.$tempContainerClass, \DIRECTORY_SEPARATOR.$realContainerClass, $file));
        }

        // remove temp kernel file after cache warmed up
        @unlink($tempKernelFile);
    }

    /**
     * @param KernelInterface $parent
     * @param string          $namespace
     * @param string          $parentClass
     * @param string          $warmupDir
     *
     * @return KernelInterface
     */
    protected function getTempKernel(KernelInterface $parent, $namespace, $parentClass, $warmupDir)
    {
        $cacheDir = var_export($warmupDir, true);
        $rootDir = var_export(realpath($parent->getRootDir()), true);
        $logDir = var_export(realpath($parent->getLogDir()), true);
        // the temp kernel class name must have the same length than the real one
        // to avoid the many problems in serialized resources files
        $class = substr($parentClass, 0, -1).'_';
        // the temp container class must be changed too
        $containerClass = var_export(substr(\get_class($parent->getContainer()), 0, -1).'_', true);
        $code = <<<EOF
<?php

namespace $namespace
{
    class $class extends $parentClass
    {
        public function getCacheDir()
        {
            return $cacheDir;
        }

        public function getRootDir()
        {
            return $rootDir;
        }

        public function getLogDir()
        {
            return $logDir;
        }

        protected function getContainerClass()
        {
            return $containerClass;
        }

        protected function buildContainer()
        {
            \$container = parent::buildContainer();

            // filter container's resources, removing reference to temp kernel file
            \$resources = \$container->getResources();
            \$filteredResources = array();
            foreach (\$resources as \$resource) {
                if ((string) \$resource !== __FILE__) {
                    \$filteredResources[] = \$resource;
                }
            }

            \$container->setResources(\$filteredResources);

            return \$container;
        }
    }
}
EOF;
        $this->getContainer()->get('filesystem')->mkdir($warmupDir);
        file_put_contents($file = $warmupDir.'/kernel.tmp', $code);
        require_once $file;
        $class = "$namespace\\$class";

        return new $class($parent->getEnvironment(), $parent->isDebug());
    }
}
