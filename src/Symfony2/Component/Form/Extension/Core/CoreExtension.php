<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Form\Extension\Core;

use Symfony2\Component\Form\AbstractExtension;
use Symfony2\Component\Form\ChoiceList\Factory\CachingFactoryDecorator;
use Symfony2\Component\Form\ChoiceList\Factory\ChoiceListFactoryInterface;
use Symfony2\Component\Form\ChoiceList\Factory\DefaultChoiceListFactory;
use Symfony2\Component\Form\ChoiceList\Factory\PropertyAccessDecorator;
use Symfony2\Component\Form\Extension\Core\Type\TransformationFailureExtension;
use Symfony2\Component\PropertyAccess\PropertyAccess;
use Symfony2\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony2\Component\Translation\TranslatorInterface;

/**
 * Represents the main form extension, which loads the core functionality.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class CoreExtension extends AbstractExtension
{
    private $propertyAccessor;
    private $choiceListFactory;
    private $translator;

    public function __construct(PropertyAccessorInterface $propertyAccessor = null, ChoiceListFactoryInterface $choiceListFactory = null, TranslatorInterface $translator = null)
    {
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
        $this->choiceListFactory = $choiceListFactory ?: new CachingFactoryDecorator(new PropertyAccessDecorator(new DefaultChoiceListFactory(), $this->propertyAccessor));
        $this->translator = $translator;
    }

    protected function loadTypes()
    {
        return array(
            new Type\FormType($this->propertyAccessor),
            new Type\BirthdayType(),
            new Type\CheckboxType(),
            new Type\ChoiceType($this->choiceListFactory),
            new Type\CollectionType(),
            new Type\CountryType(),
            new Type\DateType(),
            new Type\DateTimeType(),
            new Type\EmailType(),
            new Type\HiddenType(),
            new Type\IntegerType(),
            new Type\LanguageType(),
            new Type\LocaleType(),
            new Type\MoneyType(),
            new Type\NumberType(),
            new Type\PasswordType(),
            new Type\PercentType(),
            new Type\RadioType(),
            new Type\RangeType(),
            new Type\RepeatedType(),
            new Type\SearchType(),
            new Type\TextareaType(),
            new Type\TextType(),
            new Type\TimeType(),
            new Type\TimezoneType(),
            new Type\UrlType(),
            new Type\FileType(),
            new Type\ButtonType(),
            new Type\SubmitType(),
            new Type\ResetType(),
            new Type\CurrencyType(),
        );
    }

    protected function loadTypeExtensions()
    {
        return array(
            new TransformationFailureExtension($this->translator),
        );
    }
}
