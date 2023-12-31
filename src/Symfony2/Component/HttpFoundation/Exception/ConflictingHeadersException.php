<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\HttpFoundation\Exception;

/**
 * The HTTP request contains headers with conflicting information.
 *
 * This exception should trigger an HTTP 400 response in your application code.
 *
 * @author Magnus Nordlander <magnus@fervo.se>
 */
class ConflictingHeadersException extends \RuntimeException
{
}
