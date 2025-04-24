<?php

namespace CategoryBanner\EventListeners;

use CategoryBanner\Model\Base\BannerCategoryQuery;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\Event\Loop\LoopExtendsArgDefinitionsEvent;
use Thelia\Core\Event\Loop\LoopExtendsParseResultsEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Loop\Argument\Argument;

class ProductLoopListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            TheliaEvents::getLoopExtendsEvent(
                TheliaEvents::LOOP_EXTENDS_ARG_DEFINITIONS,
                'product'
            ) => ['productArgDefinitions', 128],
            TheliaEvents::getLoopExtendsEvent(
                TheliaEvents::LOOP_EXTENDS_PARSE_RESULTS,
                'product'
            ) => ['productParseResults', 28],
        ];
    }

    public function productArgDefinitions(LoopExtendsArgDefinitionsEvent $event): void
    {
        $argument = $event->getArgumentCollection();

        $argument->addArgument(Argument::createBooleanTypeArgument('enable_banners', false));
    }

    public function productParseResults(LoopExtendsParseResultsEvent $event):void
    {
        if (true === $event->getLoop()?->getEnableBanners() && null !== $categoryId = $event->getLoop()?->getCategory()) {
            $categoryBanners = BannerCategoryQuery::create()
                ->filterByCategoryId($categoryId)
                ->orderByPosition()
                ->find();

            $loopResult = [];

            /** @var LoopResultRow $product */
            foreach ($event->getLoopResult() as $index => $product) {
                $product->set('IS_BANNER', false);
                $product->set('BANNER_ID', null);
                $product->set('CATEGORY_BANNER_ID', null);
                $loopResult[] = clone($product);
                $event->getLoopResult()->remove($index);
            }

            foreach ($categoryBanners as $categoryBanner) {
                $insertResult = (new LoopResultRow($categoryBanner))
                    ->set('IS_BANNER', true)
                    ->set('BANNER_ID', $categoryBanner->getBannerId())
                    ->set('CATEGORY_BANNER_ID', $categoryBanner->getId());

                if ((count($loopResult)+1) >= $categoryBanner->getPosition()){
                    array_splice($loopResult, ($categoryBanner->getPosition()-1), 0, [$insertResult]);
                }
            }

            $event->getLoopResult()->setTimestamped(0);
            $event->getLoopResult()->setVersioned(0);
            $event->getLoopResult()->rewind();
            foreach ($loopResult as $index => $product) {
                $event->getLoopResult()->addRow($product, $index);
            }
        }
    }

}