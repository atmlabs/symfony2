<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\ExpressionLanguage\Tests\Node;

use PHPUnit\Framework\TestCase;
use Symfony2\Component\ExpressionLanguage\Node\ConstantNode;
use Symfony2\Component\ExpressionLanguage\Node\Node;

class NodeTest extends TestCase
{
    public function testToString()
    {
        $node = new Node(array(new ConstantNode('foo')));

        $this->assertEquals(<<<'EOF'
Node(
    ConstantNode(value: 'foo')
)
EOF
        , (string) $node);
    }

    public function testSerialization()
    {
        $node = new Node(array('foo' => 'bar'), array('bar' => 'foo'));

        $serializedNode = serialize($node);
        $unserializedNode = unserialize($serializedNode);

        $this->assertEquals($node, $unserializedNode);
    }
}
