<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\ExpressionLanguage\Tests\Fixtures;

use Symfony2\Component\ExpressionLanguage\ExpressionFunction;
use Symfony2\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class TestProvider implements ExpressionFunctionProviderInterface
{
    public function getFunctions()
    {
        return array(
            new ExpressionFunction('identity', function ($input) {
                return $input;
            }, function (array $values, $input) {
                return $input;
            }),
        );
    }
}
