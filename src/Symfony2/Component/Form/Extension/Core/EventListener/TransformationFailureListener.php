<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Form\Extension\Core\EventListener;

use Symfony2\Component\EventDispatcher\EventSubscriberInterface;
use Symfony2\Component\Form\FormError;
use Symfony2\Component\Form\FormEvent;
use Symfony2\Component\Form\FormEvents;
use Symfony2\Component\Translation\TranslatorInterface;

/**
 * @author Christian Flothmann <christian.flothmann@sensiolabs.de>
 */
class TransformationFailureListener implements EventSubscriberInterface
{
    private $translator;

    public function __construct(TranslatorInterface $translator = null)
    {
        $this->translator = $translator;
    }

    public static function getSubscribedEvents()
    {
        return array(
            FormEvents::POST_SUBMIT => array('convertTransformationFailureToFormError', -1024),
        );
    }

    public function convertTransformationFailureToFormError(FormEvent $event)
    {
        $form = $event->getForm();

        if (null === $form->getTransformationFailure() || !$form->isValid()) {
            return;
        }

        foreach ($form as $child) {
            if (!$child->isSynchronized()) {
                return;
            }
        }

        $clientDataAsString = is_scalar($form->getViewData()) ? (string) $form->getViewData() : \gettype($form->getViewData());
        $messageTemplate = 'The value {{ value }} is not valid.';

        if (null !== $this->translator) {
            $message = $this->translator->trans($messageTemplate, array('{{ value }}' => $clientDataAsString));
        } else {
            $message = strtr($messageTemplate, array('{{ value }}' => $clientDataAsString));
        }

        $form->addError(new FormError($message, $messageTemplate, array('{{ value }}' => $clientDataAsString), null, $form->getTransformationFailure()));
    }
}
