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

use Symfony2\Component\ExpressionLanguage\Node\ConstantNode;
use Symfony2\Component\ExpressionLanguage\Node\UnaryNode;

class UnaryNodeTest extends AbstractNodeTest
{
    public function getEvaluateData()
    {
        return array(
            array(-1, new UnaryNode('-', new ConstantNode(1))),
            array(3, new UnaryNode('+', new ConstantNode(3))),
            array(false, new UnaryNode('!', new ConstantNode(true))),
            array(false, new UnaryNode('not', new ConstantNode(true))),
        );
    }

    public function getCompileData()
    {
        return array(
            array('(-1)', new UnaryNode('-', new ConstantNode(1))),
            array('(+3)', new UnaryNode('+', new ConstantNode(3))),
            array('(!true)', new UnaryNode('!', new ConstantNode(true))),
            array('(!true)', new UnaryNode('not', new ConstantNode(true))),
        );
    }
}
