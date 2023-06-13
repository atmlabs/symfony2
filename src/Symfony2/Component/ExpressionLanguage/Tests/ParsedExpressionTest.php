<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\ExpressionLanguage\Tests;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\ExpressionLanguage\Node\ConstantNode;
use Symfony2\Component\ExpressionLanguage\ParsedExpression;

class ParsedExpressionTest extends TestCase
{
    public function testSerialization()
    {
        $expression = new ParsedExpression('25', new ConstantNode('25'));

        $serializedExpression = serialize($expression);
        $unserializedExpression = unserialize($serializedExpression);

        $this->assertEquals($expression, $unserializedExpression);
    }
}
