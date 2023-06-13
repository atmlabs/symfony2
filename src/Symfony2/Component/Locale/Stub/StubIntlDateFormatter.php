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

@trigger_error('The '.__NAMESPACE__.'\StubIntlDateFormatter class is deprecated since Symfony 2.3 and will be removed in 3.0. Use the Symfony2\Component\Intl\DateFormatter\IntlDateFormatter class instead.', E_USER_DEPRECATED);

use Symfony2\Component\Intl\DateFormatter\IntlDateFormatter;

/**
 * Alias of {@link \Symfony2\Component\Intl\DateFormatter\IntlDateFormatter}.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @deprecated since version 2.3, to be removed in 3.0.
 *             Use {@link \Symfony2\Component\Intl\DateFormatter\IntlDateFormatter}
 *             instead.
 */
class StubIntlDateFormatter extends IntlDateFormatter
{
}
