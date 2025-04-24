<?php

namespace CategoryBanner\Loop;

use CategoryBanner\CategoryBanner;
use CategoryBanner\Model\Banner;
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
use Thelia\Model\Base\LangQuery;
use Thelia\Type\EnumType;
use Thelia\Type\TypeCollection;

class BannerLoop extends BaseLoop implements PropelSearchLoopInterface
{
    protected function getArgDefinitions()
    {
        return new ArgumentCollection(
            Argument::createAlphaNumStringTypeArgument('id'),
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
        $query = BannerQuery::create();

        if (null !== $id = $this->getId()) {
            $query->filterById($id);
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

        /** @var Banner $data */
        foreach ($loopResult->getResultDataCollection() as $data) {

            if (null !== $width) {
                $event->setWidth($width);
            }
            if (null !== $height) {
                $event->setHeight($height);
            }
            $event->setResizeMode($resizeMode);

            $event->setSourceFilepath(CategoryBanner::CATEGORY_BANNER_IMAGE_MEDIA_FOLDER.$data->getImage());
            $event->setCacheSubdirectory('categoryBanner');

            $data->setLocale($lang?->getLocale());

            $loopResultRow = new LoopResultRow($data);

            $loopResultRow->set('ID', $data->getId());
            $loopResultRow->set('TITLE', $data->getTitle());
            $loopResultRow->set('DESCRIPTION', $data->getDescription());
            $loopResultRow->set('URL', $data->getUrl());
            $loopResultRow->set('BUTTON_LABEL', $data->getButtonLabel());

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
