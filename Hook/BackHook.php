<?php

declare(strict_types=1);

namespace CategoryBanner\Hook;

use CategoryBanner\CategoryBanner;
use CategoryBanner\Service\BannerDataService;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Thelia\Core\Event\Hook\HookRenderBlockEvent;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;
use Thelia\Core\Template\Parser\ParserResolver;
use Thelia\Model\LangQuery;
use Thelia\Tools\URL;

class BackHook extends BaseHook
{
    public function __construct(
        private readonly BannerDataService $bannerDataService,
        ?EventDispatcherInterface $dispatcher = null,
        ?ParserResolver $parserResolver = null,
    ) {
        parent::__construct($dispatcher, $parserResolver);
    }

    public static function getSubscribedHooks(): array
    {
        return [
            'main.top-menu-tools' => [
                ['type' => 'back', 'method' => 'onMainTopMenuTools'],
            ],
            'module.configuration' => [
                ['type' => 'back', 'method' => 'onModuleConfiguration'],
            ],
            'category.tab' => [
                ['type' => 'back', 'method' => 'onCategoryTab'],
            ],
        ];
    }

    public function onMainTopMenuTools(HookRenderBlockEvent $event): void
    {
        $event->add([
            'id' => 'tools_menu_categorybanner',
            'class' => '',
            'url' => URL::getInstance()?->absoluteUrl('/admin/module/CategoryBanner'),
            'title' => $this->trans('Manage banners', [], CategoryBanner::DOMAIN_NAME),
        ]);
    }

    public function onModuleConfiguration(HookRenderEvent $event): void
    {
        $event->add(
            $this->render('CategoryBanner/configuration.html.twig', [
                'banners' => $this->bannerDataService->getBanners($this->resolveLocale(), 50, 50, 'crop'),
            ])
        );
    }

    public function onCategoryTab(HookRenderBlockEvent $event): void
    {
        $categoryId = (int) $event->getArgument('id');

        if ($categoryId <= 0) {
            return;
        }

        $locale = $this->resolveLocale();

        $event->add([
            'id' => 'category_banner_tab',
            'title' => $this->trans('Banner', [], CategoryBanner::DOMAIN_NAME),
            'content' => $this->render('CategoryBanner/category-tab.html.twig', [
                'category_id' => $categoryId,
                'category_banners' => $this->bannerDataService->getCategoryBanners($categoryId, $locale),
                'banners' => $this->bannerDataService->getBanners($locale),
            ]),
        ]);
    }

    private function resolveLocale(): string
    {
        $request = $this->getRequest();
        $defaultLangId = $this->getSession()?->getLang()?->getId();
        $editLanguageId = (int) ($request?->query->get('edit_language_id') ?? $defaultLangId);

        return LangQuery::create()->findOneById($editLanguageId)?->getLocale()
            ?? LangQuery::create()->filterByByDefault(1)->findOne()?->getLocale()
            ?? 'en_US';
    }
}
