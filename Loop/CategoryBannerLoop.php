<?php

namespace CategoryBanner\Loop;

use CategoryBanner\CategoryBanner;
use CategoryBanner\Model\BannerCategory;
use CategoryBanner\Model\BannerCategoryQuery;
use CategoryBanner\Model\BannerQuery;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Thelia\Core\Event\Image\ImageEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Model\LangQuery;
use Thelia\Type\EnumType;
use Thelia\Type\TypeCollection;

class CategoryBannerLoop extends BaseLoop implements PropelSearchLoopInterface
{
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createAlphaNumStringTypeArgument('category_banner_id'),
            Argument::createAlphaNumStringTypeArgument('banner_id'),
            Argument::createAlphaNumStringTypeArgument('category_id'),
            Argument::createIntTypeArgument('width'),
            Argument::createIntTypeArgument('height'),
            new Argument(
                'resize_mode',
                new TypeCollection(
                    new EnumType(['crop', 'borders', 'none'])
                ),
                'none'
            ),
            Argument::createIntTypeArgument('lang_id')
        );
    }

    public function buildModelCriteria(): ModelCriteria
    {
        $query = BannerCategoryQuery::create();

        if (null !== $categoryBannerId = $this->getCategoryBannerId()) {
            $query->filterById($categoryBannerId);
        }

        if (null !== $bannerId = $this->getBannerId()) {
            $query->filterByBannerId($bannerId);
        }

        if (null !== $categoryId = $this->getCategoryId()) {
            $query->filterByCategoryId($categoryId);
        }

        return $query;
    }

    public function parseResults(LoopResult $loopResult)
    {
        $lang = $this->getCurrentRequest()->getSession()->get('thelia.current.lang');

        if (null !== $langId = $this->getLangId()) {
            $lang = LangQuery::create()->filterById($langId)->findOne();
        }

        switch ($this->getResizeMode()) {
            case 'crop':
                $resizeMode = \Thelia\Action\Image::EXACT_RATIO_WITH_CROP;
                break;
            case 'borders':
                $resizeMode = \Thelia\Action\Image::EXACT_RATIO_WITH_BORDERS;
                break;
            case 'none':
            default:
                $resizeMode = \Thelia\Action\Image::KEEP_IMAGE_RATIO;
        }

        $event = new ImageEvent();

        $width = $this->getWidth();
        $height = $this->getHeight();

        /** @var BannerCategory $data */
        foreach ($loopResult->getResultDataCollection() as $data) {

            if (null !== $width) {
                $event->setWidth($width);
            }
            if (null !== $height) {
                $event->setHeight($height);
            }
            $event->setResizeMode($resizeMode);
            $banner = $data->getBanner();

            $event->setSourceFilepath(CategoryBanner::CATEGORY_BANNER_IMAGE_MEDIA_FOLDER.$banner?->getImage());
            $event->setCacheSubdirectory('categoryBanner');

            $banner?->setLocale($lang?->getLocale());

            $loopResultRow = new LoopResultRow($data);


            $loopResultRow->set('ID', $data->getId());
            $loopResultRow->set('BANNER_ID', $data->getBannerId());
            $loopResultRow->set('CATEGORY_ID', $data->getCategoryId());
            $loopResultRow->set('BANNER_TITLE', $banner?->getTitle());
            $loopResultRow->set('BANNER_DESCRIPTION', $banner?->getDescription());
            $loopResultRow->set('BANNER_URL', $banner?->getUrl());
            $loopResultRow->set('BANNER_POSITION', $data->getPosition());
            $loopResultRow->set('BANNER_SIZE', $data->getSize());
            $loopResultRow->set('BANNER_BUTTON_LABEL', $banner?->getButtonLabel());

            try {
                // Dispatch image processing event
                $this->dispatcher->dispatch($event, TheliaEvents::IMAGE_PROCESS);

                $imageExt = pathinfo($event->getSourceFilepath(), \PATHINFO_EXTENSION);

                $loopResultRow
                    ->set('IMAGE_URL', $event->getFileUrl())
                    ->set('ORIGINAL_IMAGE_URL', $event->getOriginalFileUrl())
                    ->set('IMAGE_PATH', $event->getCacheFilepath())
                    ->set('PROCESSING_ERROR', false)
                    ->set('IS_SVG', 'svg' === $imageExt);

            } catch (\Exception $e) {
                $loopResultRow
                    ->set('IMAGE_URL', '')
                    ->set('ORIGINAL_IMAGE_URL', '')
                    ->set('IMAGE_PATH', '')
                    ->set('PROCESSING_ERROR', true)
                    ->set('IMAGE_HEIGHT', '')
                    ->set('IMAGE_WIDTH', '')
                ;
            }

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }


}
