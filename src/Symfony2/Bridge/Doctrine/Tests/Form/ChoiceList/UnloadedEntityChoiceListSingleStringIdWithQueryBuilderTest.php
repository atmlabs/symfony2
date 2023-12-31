<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bridge\Doctrine\Tests\Form\ChoiceList;

use Symfony2\Bridge\Doctrine\Form\ChoiceList\EntityChoiceList;
use Symfony2\Bridge\Doctrine\Form\ChoiceList\ORMQueryBuilderLoader;

if (!class_exists('Symfony2\Component\Form\Tests\Extension\Core\ChoiceList\AbstractChoiceListTest')) {
    return;
}

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 * @group legacy
 */
class UnloadedEntityChoiceListSingleStringIdWithQueryBuilderTest extends UnloadedEntityChoiceListSingleStringIdTest
{
    /**
     * @return \Symfony2\Component\Form\Extension\Core\ChoiceList\ChoiceListInterface
     */
    protected function createChoiceList()
    {
        $qb = $this->em->createQueryBuilder()->select('s')->from($this->getEntityClass(), 's');
        $loader = new ORMQueryBuilderLoader($qb);

        return new EntityChoiceList($this->em, $this->getEntityClass(), null, $loader);
    }
}
