<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\HttpKernel\Debug;

@trigger_error('The '.__NAMESPACE__.'\ExceptionHandler class is deprecated since Symfony 2.3 and will be removed in 3.0. Use the Symfony2\Component\Debug\ExceptionHandler class instead.', E_USER_DEPRECATED);

use Symfony2\Component\Debug\ExceptionHandler as DebugExceptionHandler;

/**
 * ExceptionHandler converts an exception to a Response object.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 *
 * @deprecated since version 2.3, to be removed in 3.0. Use the same class from the Debug component instead.
 */
class ExceptionHandler extends DebugExceptionHandler
{
}
