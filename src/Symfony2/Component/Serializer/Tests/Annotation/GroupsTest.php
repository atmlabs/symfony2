<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Serializer\Tests\Annotation;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\Serializer\Annotation\Groups;

/**
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class GroupsTest extends TestCase
{
    /**
     * @expectedException \Symfony2\Component\Serializer\Exception\InvalidArgumentException
     */
    public function testEmptyGroupsParameter()
    {
        new Groups(array('value' => array()));
    }

    /**
     * @expectedException \Symfony2\Component\Serializer\Exception\InvalidArgumentException
     */
    public function testNotAnArrayGroupsParameter()
    {
        new Groups(array('value' => 'coopTilleuls'));
    }

    /**
     * @expectedException \Symfony2\Component\Serializer\Exception\InvalidArgumentException
     */
    public function testInvalidGroupsParameter()
    {
        new Groups(array('value' => array('a', 1, new \stdClass())));
    }

    public function testGroupsParameters()
    {
        $validData = array('a', 'b');

        $groups = new Groups(array('value' => $validData));
        $this->assertEquals($validData, $groups->getGroups());
    }
}
