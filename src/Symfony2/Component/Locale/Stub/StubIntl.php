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

@trigger_error('The '.__NAMESPACE__.'\StubIntl class is deprecated since Symfony 2.3 and will be removed in 3.0. Use the Symfony2\Component\Intl\Globals\IntlGlobals class instead.', E_USER_DEPRECATED);

use Symfony2\Component\Intl\Globals\IntlGlobals;

/**
 * Alias of {@link \Symfony2\Component\Intl\Globals\IntlGlobals}.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @deprecated since version 2.3, to be removed in 3.0.
 *             Use {@link \Symfony2\Component\Intl\Globals\IntlGlobals} instead.
 */
abstract class StubIntl extends IntlGlobals
{
}
