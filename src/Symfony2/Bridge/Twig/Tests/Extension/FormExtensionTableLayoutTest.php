<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Bridge\Twig\Tests\Extension;

use Symfony2\Bridge\Twig\Extension\FormExtension;
use Symfony2\Bridge\Twig\Extension\TranslationExtension;
use Symfony2\Bridge\Twig\Form\TwigRenderer;
use Symfony2\Bridge\Twig\Form\TwigRendererEngine;
use Symfony2\Bridge\Twig\Tests\Extension\Fixtures\StubFilesystemLoader;
use Symfony2\Bridge\Twig\Tests\Extension\Fixtures\StubTranslator;
use Symfony2\Component\Form\FormView;
use Symfony2\Component\Form\Tests\AbstractTableLayoutTest;
use Twig\Environment;

class FormExtensionTableLayoutTest extends AbstractTableLayoutTest
{
    /**
     * @var FormExtension
     */
    protected $extension;

    protected $testableFeatures = array(
        'choice_attr',
    );

    protected function setUp()
    {
        parent::setUp();

        $rendererEngine = new TwigRendererEngine(array(
            'form_table_layout.html.twig',
            'custom_widgets.html.twig',
        ));
        $renderer = new TwigRenderer($rendererEngine, $this->getMockBuilder('Symfony2\Component\Security\Csrf\CsrfTokenManagerInterface')->getMock());

        $this->extension = new FormExtension($renderer);

        $loader = new StubFilesystemLoader(array(
            __DIR__.'/../../Resources/views/Form',
            __DIR__.'/Fixtures/templates/form',
        ));

        $environment = new Environment($loader, array('strict_variables' => true));
        $environment->addExtension(new TranslationExtension(new StubTranslator()));
        $environment->addGlobal('global', '');
        $environment->addExtension($this->extension);

        $this->extension->initRuntime($environment);
    }

    protected function tearDown()
    {
        parent::tearDown();

        $this->extension = null;
    }

    public function testStartTagHasNoActionAttributeWhenActionIsEmpty()
    {
        $form = $this->factory->create('Symfony2\Component\Form\Extension\Core\Type\FormType', null, array(
            'method' => 'get',
            'action' => '',
        ));

        $html = $this->renderStart($form->createView());

        $this->assertSame('<form name="form" method="get">', $html);
    }

    public function testStartTagHasActionAttributeWhenActionIsZero()
    {
        $form = $this->factory->create('Symfony2\Component\Form\Extension\Core\Type\FormType', null, array(
            'method' => 'get',
            'action' => '0',
        ));

        $html = $this->renderStart($form->createView());

        $this->assertSame('<form name="form" method="get" action="0">', $html);
    }

    protected function renderForm(FormView $view, array $vars = array())
    {
        return (string) $this->extension->renderer->renderBlock($view, 'form', $vars);
    }

    protected function renderEnctype(FormView $view)
    {
        return (string) $this->extension->renderer->searchAndRenderBlock($view, 'enctype');
    }

    protected function renderLabel(FormView $view, $label = null, array $vars = array())
    {
        if (null !== $label) {
            $vars += array('label' => $label);
        }

        return (string) $this->extension->renderer->searchAndRenderBlock($view, 'label', $vars);
    }

    protected function renderErrors(FormView $view)
    {
        return (string) $this->extension->renderer->searchAndRenderBlock($view, 'errors');
    }

    protected function renderWidget(FormView $view, array $vars = array())
    {
        return (string) $this->extension->renderer->searchAndRenderBlock($view, 'widget', $vars);
    }

    protected function renderRow(FormView $view, array $vars = array())
    {
        return (string) $this->extension->renderer->searchAndRenderBlock($view, 'row', $vars);
    }

    protected function renderRest(FormView $view, array $vars = array())
    {
        return (string) $this->extension->renderer->searchAndRenderBlock($view, 'rest', $vars);
    }

    protected function renderStart(FormView $view, array $vars = array())
    {
        return (string) $this->extension->renderer->renderBlock($view, 'form_start', $vars);
    }

    protected function renderEnd(FormView $view, array $vars = array())
    {
        return (string) $this->extension->renderer->renderBlock($view, 'form_end', $vars);
    }

    protected function setTheme(FormView $view, array $themes)
    {
        $this->extension->renderer->setTheme($view, $themes);
    }
}
