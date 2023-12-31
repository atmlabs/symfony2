<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Intl\ResourceBundle;

use Symfony2\Component\Intl\Data\Bundle\Reader\BundleEntryReaderInterface;
use Symfony2\Component\Intl\Data\Provider\LocaleDataProvider;
use Symfony2\Component\Intl\Data\Provider\RegionDataProvider;
use Symfony2\Component\Intl\Exception\MissingResourceException;

/**
 * Default implementation of {@link RegionBundleInterface}.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @internal
 */
class RegionBundle extends RegionDataProvider implements RegionBundleInterface
{
    private $localeProvider;

    /**
     * Creates a new region bundle.
     *
     * @param string                     $path
     * @param BundleEntryReaderInterface $reader
     * @param LocaleDataProvider         $localeProvider
     */
    public function __construct($path, BundleEntryReaderInterface $reader, LocaleDataProvider $localeProvider)
    {
        parent::__construct($path, $reader);

        $this->localeProvider = $localeProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getCountryName($country, $displayLocale = null)
    {
        try {
            return $this->getName($country, $displayLocale);
        } catch (MissingResourceException $e) {
            return;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCountryNames($displayLocale = null)
    {
        try {
            return $this->getNames($displayLocale);
        } catch (MissingResourceException $e) {
            return array();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getLocales()
    {
        try {
            return $this->localeProvider->getLocales();
        } catch (MissingResourceException $e) {
            return array();
        }
    }
}
