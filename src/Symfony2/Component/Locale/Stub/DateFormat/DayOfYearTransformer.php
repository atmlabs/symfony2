<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Locale\Stub\DateFormat;

@trigger_error('The '.__NAMESPACE__.'\DayOfYearTransformer class is deprecated since Symfony 2.3 and will be removed in 3.0. Use the Symfony2\Component\Intl\DateFormatter\DateFormat\DayOfYearTransformer class instead.', E_USER_DEPRECATED);

use Symfony2\Component\Intl\DateFormatter\DateFormat\DayOfYearTransformer as BaseDayOfYearTransformer;

/**
 * Alias of {@link \Symfony2\Component\Intl\DateFormatter\DateFormat\DayOfYearTransformer}.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @deprecated since version 2.3, to be removed in 3.0.
 *             Use {@link \Symfony2\Component\Intl\DateFormatter\DateFormat\DayOfYearTransformer}
 *             instead.
 */
class DayOfYearTransformer extends BaseDayOfYearTransformer
{
}
