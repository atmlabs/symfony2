<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Validator;

use Symfony2\Component\Validator\Constraints\GroupSequence;

/**
 * Defines the interface for a group sequence provider.
 */
interface GroupSequenceProviderInterface
{
    /**
     * Returns which validation groups should be used for a certain state
     * of the object.
     *
     * @return string[]|GroupSequence An array of validation groups
     */
    public function getGroupSequence();
}
