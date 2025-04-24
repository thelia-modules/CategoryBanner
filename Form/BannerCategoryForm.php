<?php

namespace CategoryBanner\Form;

use CategoryBanner\Model\BannerQuery;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Thelia\Form\BaseForm;
use Thelia\Model\Lang;
use Thelia\Model\LangQuery;

class BannerCategoryForm extends BaseForm
{
    protected function buildForm()
    {
        $banners = BannerQuery::create()->find();
        $choices = [];

        /** @var Lang $lang */
        $lang = $this->getRequest()->getSession()->get('thelia.current.admin_lang');

        if (null === $lang) {
            $lang = LangQuery::create()->filterByByDefault(1)->findOne();
        }

        foreach ($banners as $banner) {
            $banner->setLocale($lang->getLocale());
            $id = $banner->getId();
            $choices[$id.'-'.$banner->getTitle()] = $id;
        }

        $this->formBuilder
            ->add('banner', ChoiceType::class,
                [
                    'required' => true,
                    'choices' => $choices
                ]
            )
            ->add('position', NumberType::class,
                [
                    'required' => true,
                ]
            )
            ->add('size', NumberType::class,
                [
                    'required' => true,
                ]
            )
            ;
    }

}