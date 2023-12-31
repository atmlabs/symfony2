<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Locale\Stub;

@trigger_error('The '.__NAMESPACE__.'\StubLocale class is deprecated since Symfony 2.3 and will be removed in 3.0. Use the Symfony2\Component\Intl\Locale\Locale and Symfony2\Component\Intl\Intl classes instead.', E_USER_DEPRECATED);

use Symfony2\Component\Intl\Intl;
use Symfony2\Component\Intl\Locale\Locale;

/**
 * Alias of {@link \Symfony2\Component\Intl\Locale\Locale}.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @deprecated since version 2.3, to be removed in 3.0.
 *             Use {@link \Symfony2\Component\Intl\Locale\Locale} and
 *             {@link \Symfony2\Component\Intl\Intl} instead.
 */
class StubLocale extends Locale
{
    /**
     * Caches the currencies.
     *
     * @var array
     */
    protected static $currencies;

    /**
     * Caches the currencies names.
     *
     * @var array
     */
    protected static $currenciesNames;

    /**
     * Returns the currencies data.
     *
     * @param string $locale
     *
     * @return array The currencies data
     */
    public static function getCurrenciesData($locale)
    {
        if (null === self::$currencies) {
            self::prepareCurrencies($locale);
        }

        return self::$currencies;
    }

    /**
     *  Returns the currencies names for a locale.
     *
     * @param string $locale The locale to use for the currencies names
     *
     * @return array The currencies names with their codes as keys
     *
     * @throws \InvalidArgumentException When the locale is different than 'en'
     */
    public static function getDisplayCurrencies($locale)
    {
        if (null === self::$currenciesNames) {
            self::prepareCurrencies($locale);
        }

        return self::$currenciesNames;
    }

    /**
     * Returns all available currencies codes.
     *
     * @return array The currencies codes
     */
    public static function getCurrencies()
    {
        return array_keys(self::getCurrenciesData(self::getDefault()));
    }

    public static function getDataDirectory()
    {
        return Intl::getDataDirectory();
    }

    private static function prepareCurrencies($locale)
    {
        self::$currencies = array();
        self::$currenciesNames = array();

        $bundle = Intl::getCurrencyBundle();

        foreach ($bundle->getCurrencyNames($locale) as $currency => $name) {
            self::$currencies[$currency] = array(
                'name' => $name,
                'symbol' => $bundle->getCurrencySymbol($currency, $locale),
                'fractionDigits' => $bundle->getFractionDigits($currency),
                'roundingIncrement' => $bundle->getRoundingIncrement($currency),
            );
            self::$currenciesNames[$currency] = $name;
        }
    }
}
