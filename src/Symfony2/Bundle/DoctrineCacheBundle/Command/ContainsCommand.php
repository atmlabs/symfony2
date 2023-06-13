<?php

namespace Symfony2\Bundle\DoctrineCacheBundle\Command;

use Symfony2\Component\Console\Input\InputArgument;
use Symfony2\Component\Console\Input\InputInterface;
use Symfony2\Component\Console\Input\InputOption;
use Symfony2\Component\Console\Output\OutputInterface;

/**
 * Check if a cache entry exists.
 *
 * @author Alan Doucette <dragonwize@gmail.com>
 */
class ContainsCommand extends CacheCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('doctrine:cache:contains')
            ->setDescription('Check if a cache entry exists')
            ->addArgument('cache-name', InputArgument::REQUIRED, 'Which cache provider to use?')
            ->addArgument('cache-id', InputArgument::REQUIRED, 'Which cache ID to check?');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $cacheName     = $input->getArgument('cache-name');
        $cacheProvider = $this->getCacheProvider($cacheName);
        $cacheId       = $input->getArgument('cache-id');

        $message = $cacheProvider->contains($cacheId) ? '<info>TRUE</info>' : '<error>FALSE</error>';
        $output->writeln($message);
    }
}
