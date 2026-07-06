<?php

declare(strict_types=1);

namespace CategoryBanner\Service;

use CategoryBanner\CategoryBanner;
use CategoryBanner\Model\Banner;
use CategoryBanner\Model\BannerCategory;
use CategoryBanner\Model\BannerCategoryQuery;
use CategoryBanner\Model\BannerQuery;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Thelia\Action\Image;
use Thelia\Core\Event\Image\ImageEvent;
use Thelia\Core\Event\TheliaEvents;

/**
 * Provides banner data (with processed image URLs) to the default-twig back office.
 *
 * Twig cannot run the Thelia 2 {loop} tags, so the configuration hook, the category
 * tab hook and the banner edit controller read their data here instead. The image
 * processing mirrors what BannerLoop / CategoryBannerLoop still do for the front office.
 */
final readonly class BannerDataService
{
    public function __construct(private EventDispatcherInterface $dispatcher)
    {
    }

    /**
     * @return list<array{id:int, title:?string, description:?string, url:?string, button_label:?string, image_url:string, processing_error:bool, is_svg:bool}>
     */
    public function getBanners(?string $locale, ?int $width = null, ?int $height = null, string $resizeMode = 'none'): array
    {
        $rows = [];

        /** @var Banner $banner */
        foreach (BannerQuery::create()->orderById()->find() as $banner) {
            $rows[] = $this->mapBanner($banner, $locale, $width, $height, $resizeMode);
        }

        return $rows;
    }

    /**
     * @return array{id:int, title:?string, description:?string, url:?string, button_label:?string, image_url:string, processing_error:bool, is_svg:bool}|null
     */
    public function getBanner(int $id, ?string $locale, ?int $width = null, ?int $height = null, string $resizeMode = 'none'): ?array
    {
        $banner = BannerQuery::create()->findPk($id);

        if (null === $banner) {
            return null;
        }

        return $this->mapBanner($banner, $locale, $width, $height, $resizeMode);
    }

    /**
     * @return list<array{id:int, banner_id:?int, category_id:?int, banner_title:?string, banner_description:?string, banner_url:?string, banner_button_label:?string, position:?int, size:?int, image_url:string, processing_error:bool, is_svg:bool}>
     */
    public function getCategoryBanners(int $categoryId, ?string $locale, ?int $width = null, ?int $height = null, string $resizeMode = 'none'): array
    {
        $rows = [];

        $categoryBanners = BannerCategoryQuery::create()
            ->filterByCategoryId($categoryId)
            ->orderByPosition()
            ->find();

        /** @var BannerCategory $categoryBanner */
        foreach ($categoryBanners as $categoryBanner) {
            $banner = $categoryBanner->getBanner();
            $banner?->setLocale($locale ?? 'en_US');

            $image = $this->processImage($banner?->getImage(), $width, $height, $resizeMode);

            $rows[] = [
                'id' => (int) $categoryBanner->getId(),
                'banner_id' => $categoryBanner->getBannerId(),
                'category_id' => $categoryBanner->getCategoryId(),
                'banner_title' => $banner?->getTitle(),
                'banner_description' => $banner?->getDescription(),
                'banner_url' => $banner?->getUrl(),
                'banner_button_label' => $banner?->getButtonLabel(),
                'position' => $categoryBanner->getPosition(),
                'size' => $categoryBanner->getSize(),
                'image_url' => $image['image_url'],
                'processing_error' => $image['processing_error'],
                'is_svg' => $image['is_svg'],
            ];
        }

        return $rows;
    }

    /**
     * @return array{id:int, title:?string, description:?string, url:?string, button_label:?string, image_url:string, processing_error:bool, is_svg:bool}
     */
    private function mapBanner(Banner $banner, ?string $locale, ?int $width, ?int $height, string $resizeMode): array
    {
        $banner->setLocale($locale ?? 'en_US');

        $image = $this->processImage($banner->getImage(), $width, $height, $resizeMode);

        return [
            'id' => (int) $banner->getId(),
            'title' => $banner->getTitle(),
            'description' => $banner->getDescription(),
            'url' => $banner->getUrl(),
            'button_label' => $banner->getButtonLabel(),
            'image_url' => $image['image_url'],
            'processing_error' => $image['processing_error'],
            'is_svg' => $image['is_svg'],
        ];
    }

    /**
     * @return array{image_url:string, processing_error:bool, is_svg:bool}
     */
    private function processImage(?string $image, ?int $width, ?int $height, string $resizeMode): array
    {
        if (null === $image || '' === $image) {
            return ['image_url' => '', 'processing_error' => true, 'is_svg' => false];
        }

        $event = new ImageEvent();
        $event->setSourceFilepath(CategoryBanner::CATEGORY_BANNER_IMAGE_MEDIA_FOLDER.$image);
        $event->setCacheSubdirectory('categoryBanner');
        $event->setResizeMode((string) $this->resolveResizeMode($resizeMode));

        if (null !== $width) {
            $event->setWidth($width);
        }
        if (null !== $height) {
            $event->setHeight($height);
        }

        try {
            $this->dispatcher->dispatch($event, TheliaEvents::IMAGE_PROCESS);

            return [
                'image_url' => (string) $event->getFileUrl(),
                'processing_error' => false,
                'is_svg' => 'svg' === strtolower(pathinfo($event->getSourceFilepath(), \PATHINFO_EXTENSION)),
            ];
        } catch (\Exception) {
            return ['image_url' => '', 'processing_error' => true, 'is_svg' => false];
        }
    }

    private function resolveResizeMode(string $resizeMode): int
    {
        return match ($resizeMode) {
            'crop' => Image::EXACT_RATIO_WITH_CROP,
            'borders' => Image::EXACT_RATIO_WITH_BORDERS,
            default => Image::KEEP_IMAGE_RATIO,
        };
    }
}
