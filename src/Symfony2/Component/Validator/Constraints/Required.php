<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Validator\Constraints;

/**
 * @Annotation
 * @Target({"ANNOTATION"})
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class Required extends Existence
{
}
