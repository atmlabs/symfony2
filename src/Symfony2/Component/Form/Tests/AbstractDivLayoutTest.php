<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony2\Component\Form\Tests;

use Symfony2\Component\Form\FormError;
use Symfony2\Component\Security\Csrf\CsrfToken;

abstract class AbstractDivLayoutTest extends AbstractLayoutTest
{
    public function testRow()
    {
        $form = $this->factory->createNamed('name', 'Symfony2\Component\Form\Extension\Core\Type\TextType');
        $form->addError(new FormError('[trans]Error![/trans]'));
        $view = $form->createView();
        $html = $this->renderRow($view);

        $this->assertMatchesXpath($html,
'/div
    [
        ./label[@for="name"]
        /following-sibling::ul
            [./li[.="[trans]Error![/trans]"]]
            [count(./li)=1]
        /following-sibling::input[@id="name"]
    ]
'
        );
    }

    public function testRowOverrideVariables()
    {
        $view = $this->factory->createNamed('name', 'Symfony2\Component\Form\Extension\Core\Type\TextType')->createView();
        $html = $this->renderRow($view, array(
            'attr' => array('class' => 'my&class'),
            'label' => 'foo&bar',
            'label_attr' => array('class' => 'my&label&class'),
        ));

        $this->assertMatchesXpath($html,
'/div
    [
        ./label[@for="name"][@class="my&label&class required"][.="[trans]foo&bar[/trans]"]
        /following-sibling::input[@id="name"][@class="my&class"]
    ]
'
        );
    }

    public function testRepeatedRow()
    {
        $form = $this->factory->createNamed('name', 'Symfony2\Component\Form\Extension\Core\Type\RepeatedType');
        $form->addError(new FormError('[trans]Error![/trans]'));
        $view = $form->createView();
        $html = $this->renderRow($view);

        // The errors of the form are not rendered by intention!
        // In practice, repeated fields cannot have errors as all errors
        // on them are mapped to the first child.
        // (see RepeatedTypeValidatorExtension)

        $this->assertMatchesXpath($html,
'/div
    [
        ./label[@for="name_first"]
        /following-sibling::input[@id="name_first"]
    ]
/following-sibling::div
    [
        ./label[@for="name_second"]
        /following-sibling::input[@id="name_second"]
    ]
'
        );
    }

    public function testButtonRow()
    {
        $form = $this->factory->createNamed('name', 'Symfony2\Component\Form\Extension\Core\Type\ButtonType');
        $view = $form->createView();
        $html = $this->renderRow($view);

        $this->assertMatchesXpath($html,
'/div
    [
        ./button[@type="button"][@name="name"]
    ]
    [count(//label)=0]
'
        );
    }

    public function testRest()
    {
        $view = $this->factory->createNamedBuilder('name', 'Symfony2\Component\Form\Extension\Core\Type\FormType')
            ->add('field1', 'Symfony2\Component\Form\Extension\Core\Type\TextType')
            ->add('field2', 'Symfony2\Component\Form\Extension\Core\Type\RepeatedType')
            ->add('field3', 'Symfony2\Component\Form\Extension\Core\Type\TextType')
            ->add('field4', 'Symfony2\Component\Form\Extension\Core\Type\TextType')
            ->getForm()
            ->createView();

        // Render field2 row -> does not implicitly call renderWidget because
        // it is a repeated field!
        $this->renderRow($view['field2']);

        // Render field3 widget
        $this->renderWidget($view['field3']);

        // Rest should only contain field1 and field4
        $html = $this->renderRest($view);

        $this->assertMatchesXpath($html,
'/div
    [
        ./label[@for="name_field1"]
        /following-sibling::input[@type="text"][@id="name_field1"]
    ]
/following-sibling::div
    [
        ./label[@for="name_field4"]
        /following-sibling::input[@type="text"][@id="name_field4"]
    ]
    [count(../div)=2]
    [count(..//label)=2]
    [count(..//input)=3]
/following-sibling::input
    [@type="hidden"]
    [@id="name__token"]
'
        );
    }

    public function testRestWithChildrenForms()
    {
        $child1 = $this->factory->createNamedBuilder('child1', 'Symfony2\Component\Form\Extension\Core\Type\FormType')
            ->add('field1', 'Symfony2\Component\Form\Extension\Core\Type\TextType')
            ->add('field2', 'Symfony2\Component\Form\Extension\Core\Type\TextType');

        $child2 = $this->factory->createNamedBuilder('child2', 'Symfony2\Component\Form\Extension\Core\Type\FormType')
            ->add('field1', 'Symfony2\Component\Form\Extension\Core\Type\TextType')
            ->add('field2', 'Symfony2\Component\Form\Extension\Core\Type\TextType');

        $view = $this->factory->createNamedBuilder('parent', 'Symfony2\Component\Form\Extension\Core\Type\FormType')
            ->add($child1)
            ->add($child2)
            ->getForm()
            ->createView();

        // Render child1.field1 row
        $this->renderRow($view['child1']['field1']);

        // Render child2.field2 widget (remember that widget don't render label)
        $this->renderWidget($view['child2']['field2']);

        // Rest should only contain child1.field2 and child2.field1
        $html = $this->renderRest($view);

        $this->assertMatchesXpath($html,
'/div
    [
        ./label[not(@for)]
        /following-sibling::div[@id="parent_child1"]
            [
                ./div
                    [
                        ./label[@for="parent_child1_field2"]
                        /following-sibling::input[@id="parent_child1_field2"]
                    ]
            ]
    ]

/following-sibling::div
    [
        ./label[not(@for)]
        /following-sibling::div[@id="parent_child2"]
            [
                ./div
                    [
                        ./label[@for="parent_child2_field1"]
                        /following-sibling::input[@id="parent_child2_field1"]
                    ]
            ]
    ]
    [count(//label)=4]
    [count(//input[@type="text"])=2]
/following-sibling::input[@type="hidden"][@id="parent__token"]
'
        );
    }

    public function testRestAndRepeatedWithRow()
    {
        $view = $this->factory->createNamedBuilder('name', 'Symfony2\Component\Form\Extension\Core\Type\FormType')
            ->add('first', 'Symfony2\Component\Form\Extension\Core\Type\TextType')
            ->add('password', 'Symfony2\Component\Form\Extension\Core\Type\RepeatedType')
            ->getForm()
            ->createView();

        $this->renderRow($view['password']);

        $html = $this->renderRest($view);

        $this->assertMatchesXpath($html,
'/div
    [
        ./label[@for="name_first"]
        /following-sibling::input[@type="text"][@id="name_first"]
    ]
    [count(.//input)=1]
/following-sibling::input
    [@type="hidden"]
    [@id="name__token"]
'
        );
    }

    public function testRestAndRepeatedWithRowPerChild()
    {
        $view = $this->factory->createNamedBuilder('name', 'Symfony2\Component\Form\Extension\Core\Type\FormType')
            ->add('first', 'Symfony2\Component\Form\Extension\Core\Type\TextType')
            ->add('password', 'Symfony2\Component\Form\Extension\Core\Type\RepeatedType')
            ->getForm()
            ->createView();

        $this->renderRow($view['password']['first']);
        $this->renderRow($view['password']['second']);

        $html = $this->renderRest($view);

        $this->assertMatchesXpath($html,
'/div
    [
        ./label[@for="name_first"]
        /following-sibling::input[@type="text"][@id="name_first"]
    ]
    [count(.//input)=1]
    [count(.//label)=1]
/following-sibling::input
    [@type="hidden"]
    [@id="name__token"]
'
        );
    }

    public function testRestAndRepeatedWithWidgetPerChild()
    {
        $view = $this->factory->createNamedBuilder('name', 'Symfony2\Component\Form\Extension\Core\Type\FormType')
            ->add('first', 'Symfony2\Component\Form\Extension\Core\Type\TextType')
            ->add('password', 'Symfony2\Component\Form\Extension\Core\Type\RepeatedType')
            ->getForm()
            ->createView();

        // The password form is considered as rendered as all its children
        // are rendered
        $this->renderWidget($view['password']['first']);
        $this->renderWidget($view['password']['second']);

        $html = $this->renderRest($view);

        $this->assertMatchesXpath($html,
'/div
    [
        ./label[@for="name_first"]
        /following-sibling::input[@type="text"][@id="name_first"]
    ]
    [count(//input)=2]
    [count(//label)=1]
/following-sibling::input
    [@type="hidden"]
    [@id="name__token"]
'
        );
    }

    public function testCollection()
    {
        $form = $this->factory->createNamed('names', 'Symfony2\Component\Form\Extension\Core\Type\CollectionType', array('a', 'b'), array(
            'entry_type' => 'Symfony2\Component\Form\Extension\Core\Type\TextType',
        ));

        $this->assertWidgetMatchesXpath($form->createView(), array(),
'/div
    [
        ./div[./input[@type="text"][@value="a"]]
        /following-sibling::div[./input[@type="text"][@value="b"]]
    ]
    [count(./div[./input])=2]
'
        );
    }

    // https://github.com/symfony/symfony/issues/5038
    public function testCollectionWithAlternatingRowTypes()
    {
        $data = array(
            array('title' => 'a'),
            array('title' => 'b'),
        );
        $form = $this->factory->createNamed('names', 'Symfony2\Component\Form\Extension\Core\Type\CollectionType', $data, array(
            'entry_type' => 'Symfony2\Component\Form\Tests\Fixtures\AlternatingRowType',
        ));

        $this->assertWidgetMatchesXpath($form->createView(), array(),
'/div
    [
        ./div[./div/div/input[@type="text"][@value="a"]]
        /following-sibling::div[./div/div/textarea[.="b"]]
    ]
    [count(./div[./div/div/input])=1]
    [count(./div[./div/div/textarea])=1]
'
        );
    }

    public function testEmptyCollection()
    {
        $form = $this->factory->createNamed('names', 'Symfony2\Component\Form\Extension\Core\Type\CollectionType', array(), array(
            'entry_type' => 'Symfony2\Component\Form\Extension\Core\Type\TextType',
        ));

        $this->assertWidgetMatchesXpath($form->createView(), array(),
'/div
    [./input[@type="hidden"][@id="names__token"]]
    [count(./div)=0]
'
        );
    }

    public function testCollectionRow()
    {
        $collection = $this->factory->createNamedBuilder(
            'collection',
            'Symfony2\Component\Form\Extension\Core\Type\CollectionType',
            array('a', 'b'),
            array('entry_type' => 'Symfony2\Component\Form\Extension\Core\Type\TextType')
        );

        $form = $this->factory->createNamedBuilder('form', 'Symfony2\Component\Form\Extension\Core\Type\FormType')
            ->add($collection)
            ->getForm();

        $this->assertWidgetMatchesXpath($form->createView(), array(),
'/div
    [
        ./div
            [
                ./label[not(@for)]
                /following-sibling::div
                    [
                        ./div
                            [
                                ./label[@for="form_collection_0"]
                                /following-sibling::input[@type="text"][@value="a"]
                            ]
                        /following-sibling::div
                            [
                                ./label[@for="form_collection_1"]
                                /following-sibling::input[@type="text"][@value="b"]
                            ]
                    ]
            ]
        /following-sibling::input[@type="hidden"][@id="form__token"]
    ]
    [count(.//input)=3]
'
        );
    }

    public function testForm()
    {
        $form = $this->factory->createNamedBuilder('name', 'Symfony2\Component\Form\Extension\Core\Type\FormType')
            ->setMethod('PUT')
            ->setAction('http://example.com')
            ->add('firstName', 'Symfony2\Component\Form\Extension\Core\Type\TextType')
            ->add('lastName', 'Symfony2\Component\Form\Extension\Core\Type\TextType')
            ->getForm();

        // include ampersands everywhere to validate escaping
        $html = $this->renderForm($form->createView(), array(
            'id' => 'my&id',
            'attr' => array('class' => 'my&class'),
        ));

        $this->assertMatchesXpath($html,
'/form
    [
        ./input[@type="hidden"][@name="_method"][@value="PUT"]
        /following-sibling::div
            [
                ./div
                    [
                        ./label[@for="name_firstName"]
                        /following-sibling::input[@type="text"][@id="name_firstName"]
                    ]
                /following-sibling::div
                    [
                        ./label[@for="name_lastName"]
                        /following-sibling::input[@type="text"][@id="name_lastName"]
                    ]
                /following-sibling::input[@type="hidden"][@id="name__token"]
            ]
            [count(.//input)=3]
            [@id="my&id"]
            [@class="my&class"]
    ]
    [@method="post"]
    [@action="http://example.com"]
    [@class="my&class"]
'
        );
    }

    public function testFormWidget()
    {
        $form = $this->factory->createNamedBuilder('name', 'Symfony2\Component\Form\Extension\Core\Type\FormType')
            ->add('firstName', 'Symfony2\Component\Form\Extension\Core\Type\TextType')
            ->add('lastName', 'Symfony2\Component\Form\Extension\Core\Type\TextType')
            ->getForm();

        $this->assertWidgetMatchesXpath($form->createView(), array(),
'/div
    [
        ./div
            [
                ./label[@for="name_firstName"]
                /following-sibling::input[@type="text"][@id="name_firstName"]
            ]
        /following-sibling::div
            [
                ./label[@for="name_lastName"]
                /following-sibling::input[@type="text"][@id="name_lastName"]
            ]
        /following-sibling::input[@type="hidden"][@id="name__token"]
    ]
    [count(.//input)=3]
'
        );
    }

    // https://github.com/symfony/symfony/issues/2308
    public function testNestedFormError()
    {
        $form = $this->factory->createNamedBuilder('name', 'Symfony2\Component\Form\Extension\Core\Type\FormType')
            ->add($this->factory
                ->createNamedBuilder('child', 'Symfony2\Component\Form\Extension\Core\Type\FormType', null, array('error_bubbling' => false))
                ->add('grandChild', 'Symfony2\Component\Form\Extension\Core\Type\FormType')
            )
            ->getForm();

        $form->get('child')->addError(new FormError('[trans]Error![/trans]'));

        $this->assertWidgetMatchesXpath($form->createView(), array(),
'/div
    [
        ./div/label
        /following-sibling::ul[./li[.="[trans]Error![/trans]"]]
    ]
    [count(.//li[.="[trans]Error![/trans]"])=1]
'
        );
    }

    public function testCsrf()
    {
        $this->csrfTokenManager->expects($this->any())
            ->method('getToken')
            ->will($this->returnValue(new CsrfToken('token_id', 'foo&bar')));

        $form = $this->factory->createNamedBuilder('name', 'Symfony2\Component\Form\Extension\Core\Type\FormType')
            ->add($this->factory
                // No CSRF protection on nested forms
                ->createNamedBuilder('child', 'Symfony2\Component\Form\Extension\Core\Type\FormType')
                ->add($this->factory->createNamedBuilder('grandchild', 'Symfony2\Component\Form\Extension\Core\Type\TextType'))
            )
            ->getForm();

        $this->assertWidgetMatchesXpath($form->createView(), array(),
'/div
    [
        ./div
        /following-sibling::input[@type="hidden"][@id="name__token"][@value="foo&bar"]
    ]
    [count(.//input[@type="hidden"])=1]
'
        );
    }

    public function testRepeated()
    {
        $form = $this->factory->createNamed('name', 'Symfony2\Component\Form\Extension\Core\Type\RepeatedType', 'foobar', array(
            'type' => 'Symfony2\Component\Form\Extension\Core\Type\TextType',
        ));

        $this->assertWidgetMatchesXpath($form->createView(), array(),
'/div
    [
        ./div
            [
                ./label[@for="name_first"]
                /following-sibling::input[@type="text"][@id="name_first"]
            ]
        /following-sibling::div
            [
                ./label[@for="name_second"]
                /following-sibling::input[@type="text"][@id="name_second"]
            ]
        /following-sibling::input[@type="hidden"][@id="name__token"]
    ]
    [count(.//input)=3]
'
        );
    }

    public function testRepeatedWithCustomOptions()
    {
        $form = $this->factory->createNamed('name', 'Symfony2\Component\Form\Extension\Core\Type\RepeatedType', null, array(
            // the global required value cannot be overridden
            'first_options' => array('label' => 'Test', 'required' => false),
            'second_options' => array('label' => 'Test2'),
        ));

        $this->assertWidgetMatchesXpath($form->createView(), array(),
'/div
    [
        ./div
            [
                ./label[@for="name_first"][.="[trans]Test[/trans]"]
                /following-sibling::input[@type="text"][@id="name_first"][@required="required"]
            ]
        /following-sibling::div
            [
                ./label[@for="name_second"][.="[trans]Test2[/trans]"]
                /following-sibling::input[@type="text"][@id="name_second"][@required="required"]
            ]
        /following-sibling::input[@type="hidden"][@id="name__token"]
    ]
    [count(.//input)=3]
'
        );
    }

    public function testSearchInputName()
    {
        $form = $this->factory->createNamedBuilder('full', 'Symfony2\Component\Form\Extension\Core\Type\FormType')
            ->add('name', 'Symfony2\Component\Form\Extension\Core\Type\SearchType')
            ->getForm();

        $this->assertWidgetMatchesXpath($form->createView(), array(),
'/div
    [
        ./div
            [
                ./label[@for="full_name"]
                /following-sibling::input[@type="search"][@id="full_name"][@name="full[name]"]
            ]
        /following-sibling::input[@type="hidden"][@id="full__token"]
    ]
    [count(//input)=2]
'
        );
    }

    public function testLabelHasNoId()
    {
        $form = $this->factory->createNamed('name', 'Symfony2\Component\Form\Extension\Core\Type\TextType');
        $html = $this->renderRow($form->createView());

        $this->assertMatchesXpath($html,
'/div
    [
        ./label[@for="name"][not(@id)]
        /following-sibling::input[@id="name"]
    ]
'
        );
    }

    public function testLabelIsNotRenderedWhenSetToFalse()
    {
        $form = $this->factory->createNamed('name', 'Symfony2\Component\Form\Extension\Core\Type\TextType', null, array(
            'label' => false,
        ));
        $html = $this->renderRow($form->createView());

        $this->assertMatchesXpath($html,
'/div
    [
        ./input[@id="name"]
    ]
    [count(//label)=0]
'
        );
    }

    /**
     * @dataProvider themeBlockInheritanceProvider
     */
    public function testThemeBlockInheritance($theme)
    {
        $view = $this->factory
            ->createNamed('name', 'Symfony2\Component\Form\Extension\Core\Type\EmailType')
            ->createView()
        ;

        $this->setTheme($view, $theme);

        $this->assertMatchesXpath(
            $this->renderWidget($view),
            '/input[@type="email"][@rel="theme"]'
        );
    }

    /**
     * @dataProvider themeInheritanceProvider
     */
    public function testThemeInheritance($parentTheme, $childTheme)
    {
        $child = $this->factory->createNamedBuilder('child', 'Symfony2\Component\Form\Extension\Core\Type\FormType')
            ->add('field', 'Symfony2\Component\Form\Extension\Core\Type\TextType');

        $view = $this->factory->createNamedBuilder('parent', 'Symfony2\Component\Form\Extension\Core\Type\FormType')
            ->add('field', 'Symfony2\Component\Form\Extension\Core\Type\TextType')
            ->add($child)
            ->getForm()
            ->createView()
        ;

        $this->setTheme($view, $parentTheme);
        $this->setTheme($view['child'], $childTheme);

        $this->assertWidgetMatchesXpath($view, array(),
'/div
    [
        ./div
            [
                ./label[.="parent"]
                /following-sibling::input[@type="text"]
            ]
        /following-sibling::div
            [
                ./label[.="child"]
                /following-sibling::div
                    [
                        ./div
                            [
                                ./label[.="child"]
                                /following-sibling::input[@type="text"]
                            ]
                    ]
            ]
        /following-sibling::input[@type="hidden"]
    ]
'
        );
    }

    /**
     * The block "_name_child_label" should be overridden in the theme of the
     * implemented driver.
     */
    public function testCollectionRowWithCustomBlock()
    {
        $collection = array('one', 'two', 'three');
        $form = $this->factory->createNamedBuilder('names', 'Symfony2\Component\Form\Extension\Core\Type\CollectionType', $collection)
            ->getForm();

        $this->assertWidgetMatchesXpath($form->createView(), array(),
'/div
    [
        ./div[./label[.="Custom label: [trans]0[/trans]"]]
        /following-sibling::div[./label[.="Custom label: [trans]1[/trans]"]]
        /following-sibling::div[./label[.="Custom label: [trans]2[/trans]"]]
    ]
'
        );
    }

    /**
     * The block "_name_c_entry_label" should be overridden in the theme of the
     * implemented driver.
     */
    public function testChoiceRowWithCustomBlock()
    {
        $form = $this->factory->createNamedBuilder('name_c', 'Symfony2\Component\Form\Extension\Core\Type\ChoiceType', 'a', array(
                'choices' => array('ChoiceA' => 'a', 'ChoiceB' => 'b'),
                'choices_as_values' => true,
                'expanded' => true,
            ))
            ->getForm();

        $this->assertWidgetMatchesXpath($form->createView(), array(),
'/div
    [
        ./label[.="Custom name label: [trans]ChoiceA[/trans]"]
        /following-sibling::label[.="Custom name label: [trans]ChoiceB[/trans]"]
    ]
'
        );
    }

    public function testSingleChoiceExpandedWithLabelsAsFalse()
    {
        $form = $this->factory->createNamed('name', 'Symfony2\Component\Form\Extension\Core\Type\ChoiceType', '&a', array(
            'choices' => array('Choice&A' => '&a', 'Choice&B' => '&b'),
            'choices_as_values' => true,
            'choice_label' => false,
            'multiple' => false,
            'expanded' => true,
        ));

        $this->assertWidgetMatchesXpath($form->createView(), array(),
            '/div
    [
        ./input[@type="radio"][@name="name"][@id="name_0"][@value="&a"][@checked]
        /following-sibling::input[@type="radio"][@name="name"][@id="name_1"][@value="&b"][not(@checked)]
        /following-sibling::input[@type="hidden"][@id="name__token"]
    ]
    [count(./input)=3]
    [count(./label)=1]
'
        );
    }

    public function testSingleChoiceExpandedWithLabelsSetByCallable()
    {
        $form = $this->factory->createNamed('name', 'Symfony2\Component\Form\Extension\Core\Type\ChoiceType', '&a', array(
            'choices' => array('Choice&A' => '&a', 'Choice&B' => '&b', 'Choice&C' => '&c'),
            'choices_as_values' => true,
            'choice_label' => function ($choice, $label, $value) {
                if ('&b' === $choice) {
                    return false;
                }

                return 'label.'.$value;
            },
            'multiple' => false,
            'expanded' => true,
        ));

        $this->assertWidgetMatchesXpath($form->createView(), array(),
            '/div
    [
        ./input[@type="radio"][@name="name"][@id="name_0"][@value="&a"][@checked]
        /following-sibling::label[@for="name_0"][.="[trans]label.&a[/trans]"]
        /following-sibling::input[@type="radio"][@name="name"][@id="name_1"][@value="&b"][not(@checked)]
        /following-sibling::input[@type="radio"][@name="name"][@id="name_2"][@value="&c"][not(@checked)]
        /following-sibling::label[@for="name_2"][.="[trans]label.&c[/trans]"]
        /following-sibling::input[@type="hidden"][@id="name__token"]
    ]
    [count(./input)=4]
    [count(./label)=3]
'
        );
    }

    public function testSingleChoiceExpandedWithLabelsSetFalseByCallable()
    {
        $form = $this->factory->createNamed('name', 'Symfony2\Component\Form\Extension\Core\Type\ChoiceType', '&a', array(
            'choices' => array('Choice&A' => '&a', 'Choice&B' => '&b'),
            'choices_as_values' => true,
            'choice_label' => function () {
                return false;
            },
            'multiple' => false,
            'expanded' => true,
        ));

        $this->assertWidgetMatchesXpath($form->createView(), array(),
            '/div
    [
        ./input[@type="radio"][@name="name"][@id="name_0"][@value="&a"][@checked]
        /following-sibling::input[@type="radio"][@name="name"][@id="name_1"][@value="&b"][not(@checked)]
        /following-sibling::input[@type="hidden"][@id="name__token"]
    ]
    [count(./input)=3]
    [count(./label)=1]
'
        );
    }

    public function testMultipleChoiceExpandedWithLabelsAsFalse()
    {
        $form = $this->factory->createNamed('name', 'Symfony2\Component\Form\Extension\Core\Type\ChoiceType', array('&a'), array(
            'choices' => array('Choice&A' => '&a', 'Choice&B' => '&b'),
            'choices_as_values' => true,
            'choice_label' => false,
            'multiple' => true,
            'expanded' => true,
        ));

        $this->assertWidgetMatchesXpath($form->createView(), array(),
'/div
    [
        ./input[@type="checkbox"][@name="name[]"][@id="name_0"][@value="&a"][@checked]
        /following-sibling::input[@type="checkbox"][@name="name[]"][@id="name_1"][@value="&b"][not(@checked)]
        /following-sibling::input[@type="hidden"][@id="name__token"]
    ]
    [count(./input)=3]
    [count(./label)=1]
'
        );
    }

    public function testMultipleChoiceExpandedWithLabelsSetByCallable()
    {
        $form = $this->factory->createNamed('name', 'Symfony2\Component\Form\Extension\Core\Type\ChoiceType', array('&a'), array(
            'choices' => array('Choice&A' => '&a', 'Choice&B' => '&b', 'Choice&C' => '&c'),
            'choices_as_values' => true,
            'choice_label' => function ($choice, $label, $value) {
                if ('&b' === $choice) {
                    return false;
                }

                return 'label.'.$value;
            },
            'multiple' => true,
            'expanded' => true,
        ));

        $this->assertWidgetMatchesXpath($form->createView(), array(),
'/div
    [
        ./input[@type="checkbox"][@name="name[]"][@id="name_0"][@value="&a"][@checked]
        /following-sibling::label[@for="name_0"][.="[trans]label.&a[/trans]"]
        /following-sibling::input[@type="checkbox"][@name="name[]"][@id="name_1"][@value="&b"][not(@checked)]
        /following-sibling::input[@type="checkbox"][@name="name[]"][@id="name_2"][@value="&c"][not(@checked)]
        /following-sibling::label[@for="name_2"][.="[trans]label.&c[/trans]"]
        /following-sibling::input[@type="hidden"][@id="name__token"]
    ]
    [count(./input)=4]
    [count(./label)=3]
'
        );
    }

    public function testMultipleChoiceExpandedWithLabelsSetFalseByCallable()
    {
        $form = $this->factory->createNamed('name', 'Symfony2\Component\Form\Extension\Core\Type\ChoiceType', array('&a'), array(
            'choices' => array('Choice&A' => '&a', 'Choice&B' => '&b'),
            'choices_as_values' => true,
            'choice_label' => function () {
                return false;
            },
            'multiple' => true,
            'expanded' => true,
        ));

        $this->assertWidgetMatchesXpath($form->createView(), array(),
'/div
    [
        ./input[@type="checkbox"][@name="name[]"][@id="name_0"][@value="&a"][@checked]
        /following-sibling::input[@type="checkbox"][@name="name[]"][@id="name_1"][@value="&b"][not(@checked)]
        /following-sibling::input[@type="hidden"][@id="name__token"]
    ]
    [count(./input)=3]
    [count(./label)=1]
'
        );
    }

    public function testFormEndWithRest()
    {
        $view = $this->factory->createNamedBuilder('name', 'Symfony2\Component\Form\Extension\Core\Type\FormType')
            ->add('field1', 'Symfony2\Component\Form\Extension\Core\Type\TextType')
            ->add('field2', 'Symfony2\Component\Form\Extension\Core\Type\TextType')
            ->getForm()
            ->createView();

        $this->renderWidget($view['field1']);

        // Rest should only contain field2
        $html = $this->renderEnd($view);

        // Insert the start tag, the end tag should be rendered by the helper
        $this->assertMatchesXpath('<form>'.$html,
'/form
    [
        ./div
            [
                ./label[@for="name_field2"]
                /following-sibling::input[@type="text"][@id="name_field2"]
            ]
        /following-sibling::input
            [@type="hidden"]
            [@id="name__token"]
    ]
'
        );
    }

    public function testFormEndWithoutRest()
    {
        $view = $this->factory->createNamedBuilder('name', 'Symfony2\Component\Form\Extension\Core\Type\FormType')
            ->add('field1', 'Symfony2\Component\Form\Extension\Core\Type\TextType')
            ->add('field2', 'Symfony2\Component\Form\Extension\Core\Type\TextType')
            ->getForm()
            ->createView();

        $this->renderWidget($view['field1']);

        // Rest should only contain field2, but isn't rendered
        $html = $this->renderEnd($view, array('render_rest' => false));

        $this->assertEquals('</form>', $html);
    }

    public function testWidgetContainerAttributes()
    {
        $form = $this->factory->createNamed('form', 'Symfony2\Component\Form\Extension\Core\Type\FormType', null, array(
            'attr' => array('class' => 'foobar', 'data-foo' => 'bar'),
        ));

        $form->add('text', 'Symfony2\Component\Form\Extension\Core\Type\TextType');

        $html = $this->renderWidget($form->createView());

        // compare plain HTML to check the whitespace
        $this->assertContains('<div id="form" class="foobar" data-foo="bar">', $html);
    }

    public function testWidgetContainerAttributeNameRepeatedIfTrue()
    {
        $form = $this->factory->createNamed('form', 'Symfony2\Component\Form\Extension\Core\Type\FormType', null, array(
            'attr' => array('foo' => true),
        ));

        $html = $this->renderWidget($form->createView());

        // foo="foo"
        $this->assertContains('<div id="form" foo="foo">', $html);
    }
}
