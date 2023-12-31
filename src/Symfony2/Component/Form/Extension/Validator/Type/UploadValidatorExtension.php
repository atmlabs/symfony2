<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Form\Extension\Validator\Type;

use Symfony2\Component\Form\AbstractTypeExtension;
use Symfony2\Component\OptionsResolver\Options;
use Symfony2\Component\OptionsResolver\OptionsResolver;
use Symfony2\Component\Translation\TranslatorInterface;

/**
 * @author Abdellatif Ait boudad <a.aitboudad@gmail.com>
 * @author David Badura <d.a.badura@gmail.com>
 */
class UploadValidatorExtension extends AbstractTypeExtension
{
    private $translator;
    private $translationDomain;

    /**
     * @param TranslatorInterface $translator        The translator for translating error messages
     * @param string|null         $translationDomain The translation domain for translating
     */
    public function __construct(TranslatorInterface $translator, $translationDomain = null)
    {
        $this->translator = $translator;
        $this->translationDomain = $translationDomain;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $translator = $this->translator;
        $translationDomain = $this->translationDomain;
        $resolver->setNormalizer('upload_max_size_message', function (Options $options, $message) use ($translator, $translationDomain) {
            return function () use ($translator, $translationDomain, $message) {
                return $translator->trans(\call_user_func($message), array(), $translationDomain);
            };
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return 'Symfony2\Component\Form\Extension\Core\Type\FormType';
    }
}
