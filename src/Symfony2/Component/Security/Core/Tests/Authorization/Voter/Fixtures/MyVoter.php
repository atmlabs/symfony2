<?php

namespace Symfony2\Component\Security\Core\Tests\Authorization\Voter\Fixtures;

use Symfony2\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony2\Component\Security\Core\Authorization\Voter\AbstractVoter;

/**
 * @group legacy
 */
class MyVoter extends AbstractVoter
{
    protected function getSupportedClasses()
    {
        return array('stdClass');
    }

    protected function getSupportedAttributes()
    {
        return array('EDIT', 'CREATE');
    }

    protected function isGranted($attribute, $object, $user = null)
    {
        return 'EDIT' === $attribute;
    }
}
