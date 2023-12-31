<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bundle\FrameworkBundle\Translation;

use Symfony2\Component\Finder\Finder;
use Symfony2\Component\Translation\Loader\LoaderInterface;
use Symfony2\Component\Translation\MessageCatalogue;

/**
 * TranslationLoader loads translation messages from translation files.
 *
 * @author Michel Salib <michelsalib@hotmail.com>
 */
class TranslationLoader
{
    /**
     * Loaders used for import.
     *
     * @var array
     */
    private $loaders = array();

    /**
     * Adds a loader to the translation extractor.
     *
     * @param string          $format The format of the loader
     * @param LoaderInterface $loader
     */
    public function addLoader($format, LoaderInterface $loader)
    {
        $this->loaders[$format] = $loader;
    }

    /**
     * Loads translation messages from a directory to the catalogue.
     *
     * @param string           $directory The directory to look into
     * @param MessageCatalogue $catalogue The catalogue
     */
    public function loadMessages($directory, MessageCatalogue $catalogue)
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach ($this->loaders as $format => $loader) {
            // load any existing translation files
            $finder = new Finder();
            $extension = $catalogue->getLocale().'.'.$format;
            $files = $finder->files()->name('*.'.$extension)->in($directory);
            foreach ($files as $file) {
                $domain = substr($file->getFilename(), 0, -1 * \strlen($extension) - 1);
                $catalogue->addCatalogue($loader->load($file->getPathname(), $catalogue->getLocale(), $domain));
            }
        }
    }
}
