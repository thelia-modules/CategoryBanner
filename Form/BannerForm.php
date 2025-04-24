<?php

namespace CategoryBanner\Form;

use CategoryBanner\CategoryBanner;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints;
use Thelia\Core\Translation\Translator;
use Thelia\Form\BaseForm;

class BannerForm extends BaseForm
{
    protected function buildForm()
    {
        $this->formBuilder
            ->add('title', TextType::class, [
                'required' => true,
                'label'=> Translator::getInstance()->trans(
                    'Title',
                    array(),
                    CategoryBanner::DOMAIN_NAME
                ),
                'constraints' => [
                    new Constraints\NotBlank()
                ],
                'label_attr' => array(
                    'for' => 'title'
                )
            ])
            ->add('description', TextType::class, [
                'required' => false,
                'label'=> Translator::getInstance()->trans(
                    'Description',
                    array(),
                    CategoryBanner::DOMAIN_NAME
                ),
                'label_attr' => array(
                    'for' => 'description'
                )
            ])
            ->add('url', TextType::class, [
                'required' => false,
                'label'=> Translator::getInstance()->trans(
                    'URL',
                    array(),
                    CategoryBanner::DOMAIN_NAME
                ),
                'label_attr' => array(
                    'for' => 'url'
                )
            ])
            ->add('button_label', TextType::class, [
                'required' => false,
                'label'=> Translator::getInstance()->trans(
                    'Label du bouton',
                    array(),
                    CategoryBanner::DOMAIN_NAME
                ),
                'label_attr' => array(
                    'for' => 'url'
                )
            ])
            ->add('image_file', FileType::class, [
                'required' => false,
                'label'=> Translator::getInstance()->trans(
                    'Image',
                    array(),
                    CategoryBanner::DOMAIN_NAME
                ),
                'constraints' => [
                    new Constraints\File(['mimeTypes' => ['image/jpeg', 'image/png', 'image/svg+xml']])
                ],
                'label_attr' => array(
                    'for' => 'image_file'
                )
            ]);
    }


}