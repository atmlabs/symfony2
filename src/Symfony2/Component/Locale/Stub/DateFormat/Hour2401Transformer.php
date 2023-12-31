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

@trigger_error('The '.__NAMESPACE__.'\Hour2401Transformer class is deprecated since Symfony 2.3 and will be removed in 3.0. Use the Symfony2\Component\Intl\DateFormatter\DateFormat\Hour2401Transformer class instead.', E_USER_DEPRECATED);

use Symfony2\Component\Intl\DateFormatter\DateFormat\Hour2401Transformer as BaseHour2401Transformer;

/**
 * Alias of {@link \Symfony2\Component\Intl\DateFormatter\DateFormat\Hour2401Transformer}.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 *
 * @deprecated since version 2.3, to be removed in 3.0.
 *             Use {@link \Symfony2\Component\Intl\DateFormatter\DateFormat\Hour2401Transformer}
 *             instead.
 */
class Hour2401Transformer extends BaseHour2401Transformer
{
}
