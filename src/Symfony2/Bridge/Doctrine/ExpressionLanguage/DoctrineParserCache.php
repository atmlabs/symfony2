<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bridge\Doctrine\ExpressionLanguage;

use Doctrine\Common\Cache\Cache;
use Symfony2\Component\ExpressionLanguage\ParsedExpression;
use Symfony2\Component\ExpressionLanguage\ParserCache\ParserCacheInterface;

/**
 * @author Adrien Brault <adrien.brault@gmail.com>
 */
class DoctrineParserCache implements ParserCacheInterface
{
    private $cache;

    public function __construct(Cache $cache)
    {
        $this->cache = $cache;
    }

    /**
     * {@inheritdoc}
     */
    public function fetch($key)
    {
        if (false === $value = $this->cache->fetch($key)) {
            return;
        }

        return $value;
    }

    /**
     * {@inheritdoc}
     */
    public function save($key, ParsedExpression $expression)
    {
        $this->cache->save($key, $expression);
    }
}
