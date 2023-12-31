<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Form\Extension\HttpFoundation\Type;

use Symfony2\Component\Form\AbstractTypeExtension;
use Symfony2\Component\Form\Extension\HttpFoundation\EventListener\BindRequestListener;
use Symfony2\Component\Form\Extension\HttpFoundation\HttpFoundationRequestHandler;
use Symfony2\Component\Form\FormBuilderInterface;
use Symfony2\Component\Form\RequestHandlerInterface;

/**
 * @author Bernhard Schussek <bschussek@gmail.com>
 */
class FormTypeHttpFoundationExtension extends AbstractTypeExtension
{
    private $listener;
    private $requestHandler;

    public function __construct(RequestHandlerInterface $requestHandler = null)
    {
        $this->listener = new BindRequestListener();
        $this->requestHandler = $requestHandler ?: new HttpFoundationRequestHandler();
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber($this->listener);
        $builder->setRequestHandler($this->requestHandler);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'Symfony2\Component\Form\Extension\Core\Type\FormType';
    }
}
