<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bridge\Doctrine\Form;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony2\Bridge\Doctrine\Form\Type\EntityType;
use Symfony2\Component\Form\AbstractExtension;
use Symfony2\Component\Form\ChoiceList\Factory\CachingFactoryDecorator;
use Symfony2\Component\Form\ChoiceList\Factory\ChoiceListFactoryInterface;
use Symfony2\Component\Form\ChoiceList\Factory\DefaultChoiceListFactory;
use Symfony2\Component\Form\ChoiceList\Factory\PropertyAccessDecorator;
use Symfony2\Component\PropertyAccess\PropertyAccess;
use Symfony2\Component\PropertyAccess\PropertyAccessorInterface;

class DoctrineOrmExtension extends AbstractExtension
{
    protected $registry;

    private $propertyAccessor;
    private $choiceListFactory;

    public function __construct(ManagerRegistry $registry, PropertyAccessorInterface $propertyAccessor = null, ChoiceListFactoryInterface $choiceListFactory = null)
    {
        $this->registry = $registry;
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
        $this->choiceListFactory = $choiceListFactory ?: new CachingFactoryDecorator(new PropertyAccessDecorator(new DefaultChoiceListFactory(), $this->propertyAccessor));
    }

    protected function loadTypes()
    {
        return array(
            new EntityType($this->registry, $this->propertyAccessor, $this->choiceListFactory),
        );
    }

    protected function loadTypeGuesser()
    {
        return new DoctrineOrmTypeGuesser($this->registry);
    }
}
