<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Form\Extension\Core\DataMapper;

use Symfony2\Component\Form\DataMapperInterface;
use Symfony2\Component\Form\Exception\UnexpectedTypeException;
use Symfony2\Component\PropertyAccess\PropertyAccess;
use Symfony2\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Maps arrays/objects to/from forms using property paths.
 *
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class PropertyPathMapper implements DataMapperInterface
{
    private $propertyAccessor;

    public function __construct(PropertyAccessorInterface $propertyAccessor = null)
    {
        $this->propertyAccessor = $propertyAccessor ?: PropertyAccess::createPropertyAccessor();
    }

    /**
     * {@inheritdoc}
     */
    public function mapDataToForms($data, $forms)
    {
        $empty = null === $data || array() === $data;

        if (!$empty && !\is_array($data) && !\is_object($data)) {
            throw new UnexpectedTypeException($data, 'object, array or empty');
        }

        foreach ($forms as $form) {
            $propertyPath = $form->getPropertyPath();
            $config = $form->getConfig();

            if (!$empty && null !== $propertyPath && $config->getMapped()) {
                $form->setData($this->propertyAccessor->getValue($data, $propertyPath));
            } else {
                $form->setData($form->getConfig()->getData());
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function mapFormsToData($forms, &$data)
    {
        if (null === $data) {
            return;
        }

        if (!\is_array($data) && !\is_object($data)) {
            throw new UnexpectedTypeException($data, 'object, array or empty');
        }

        foreach ($forms as $form) {
            $propertyPath = $form->getPropertyPath();
            $config = $form->getConfig();

            // Write-back is disabled if the form is not synchronized (transformation failed),
            // if the form was not submitted and if the form is disabled (modification not allowed)
            if (null !== $propertyPath && $config->getMapped() && $form->isSubmitted() && $form->isSynchronized() && !$form->isDisabled()) {
                $propertyValue = $form->getData();
                // If the field is of type DateTime or DateTimeInterface and the data is the same skip the update to
                // keep the original object hash
                if (($propertyValue instanceof \DateTime || $propertyValue instanceof \DateTimeInterface) && $propertyValue == $this->propertyAccessor->getValue($data, $propertyPath)) {
                    continue;
                }

                // If the data is identical to the value in $data, we are
                // dealing with a reference
                if (!\is_object($data) || !$config->getByReference() || $propertyValue !== $this->propertyAccessor->getValue($data, $propertyPath)) {
                    $this->propertyAccessor->setValue($data, $propertyPath, $propertyValue);
                }
            }
        }
    }
}
