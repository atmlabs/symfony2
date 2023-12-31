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

use Symfony2\Component\Validator\Constraint;
use Symfony2\Component\Validator\Exception\ConstraintDefinitionException;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class Valid extends Constraint
{
    public $traverse = true;

    /**
     * @deprecated since version 2.5, to be removed in Symfony 3.0.
     */
    public $deep = true;

    public function __construct($options = null)
    {
        if (\is_array($options) && array_key_exists('groups', $options)) {
            throw new ConstraintDefinitionException(sprintf('The option "groups" is not supported by the constraint %s', __CLASS__));
        }

        if (\is_array($options) && array_key_exists('deep', $options)) {
            @trigger_error('The "deep" option for the Valid constraint is deprecated since Symfony 2.5 and will be removed in 3.0. When traversing arrays, nested arrays are always traversed. When traversing nested objects, their traversal strategy is used.', E_USER_DEPRECATED);
        }

        parent::__construct($options);
    }
}
