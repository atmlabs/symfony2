<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Validator\Tests\Constraints;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\Validator\Constraints\Collection;
use Symfony2\Component\Validator\Constraints\Email;
use Symfony2\Component\Validator\Constraints\Optional;
use Symfony2\Component\Validator\Constraints\Required;
use Symfony2\Component\Validator\Constraints\Valid;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class CollectionTest extends TestCase
{
    /**
     * @expectedException \Symfony2\Component\Validator\Exception\ConstraintDefinitionException
     */
    public function testRejectInvalidFieldsOption()
    {
        new Collection(array(
            'fields' => 'foo',
        ));
    }

    /**
     * @expectedException \Symfony2\Component\Validator\Exception\ConstraintDefinitionException
     */
    public function testRejectNonConstraints()
    {
        new Collection(array(
            'foo' => 'bar',
        ));
    }

    /**
     * @expectedException \Symfony2\Component\Validator\Exception\ConstraintDefinitionException
     */
    public function testRejectValidConstraint()
    {
        new Collection(array(
            'foo' => new Valid(),
        ));
    }

    /**
     * @expectedException \Symfony2\Component\Validator\Exception\ConstraintDefinitionException
     */
    public function testRejectValidConstraintWithinOptional()
    {
        new Collection(array(
            'foo' => new Optional(new Valid()),
        ));
    }

    /**
     * @expectedException \Symfony2\Component\Validator\Exception\ConstraintDefinitionException
     */
    public function testRejectValidConstraintWithinRequired()
    {
        new Collection(array(
            'foo' => new Required(new Valid()),
        ));
    }

    public function testAcceptOptionalConstraintAsOneElementArray()
    {
        $collection1 = new Collection(array(
            'fields' => array(
                'alternate_email' => array(
                    new Optional(new Email()),
                ),
            ),
        ));

        $collection2 = new Collection(array(
            'fields' => array(
                'alternate_email' => new Optional(new Email()),
            ),
        ));

        $this->assertEquals($collection1, $collection2);
    }

    public function testAcceptRequiredConstraintAsOneElementArray()
    {
        $collection1 = new Collection(array(
            'fields' => array(
                'alternate_email' => array(
                    new Required(new Email()),
                ),
            ),
        ));

        $collection2 = new Collection(array(
            'fields' => array(
                'alternate_email' => new Required(new Email()),
            ),
        ));

        $this->assertEquals($collection1, $collection2);
    }
}
