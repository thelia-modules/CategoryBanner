<?php

namespace CategoryBanner\Hook;

use CategoryBanner\CategoryBanner;
use Thelia\Core\Event\Hook\HookRenderBlockEvent;
use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;
use Thelia\Tools\URL;

class BackHook extends BaseHook
{
    public function onMainTopMenuItems(HookRenderEvent $event): void
    {
        $event->add($this->render("menu-hook.html", $event->getArguments()));
    }

    public function onModuleConfiguration(HookRenderEvent $event): void
    {
        $event->add($this->render("configuration.html", $event->getArguments()));
    }

    public function onModuleConfigurationJs(HookRenderEvent $event): void
    {
        if ($event->getArgument('modulecode') === 'CategoryBanner') {
            $event->add($this->render("configurationJs.html", $event->getArguments()));
        }
    }
    public function onCategoryEditJs(HookRenderEvent $event): void
    {
        $event->add($this->render("category-tab-js.html", $event->getArguments()));
    }

    public function onCategoryTab(HookRenderBlockEvent $event)
    {
        $event->add(
            [
                'id' => 'category_banner_tab',
                'title' => $this->trans("Bannière", [], CategoryBanner::DOMAIN_NAME),
                'content' => $this->render(
                    'category-tab.html',
                    [
                        'category_id' => $event->getArgument('id')
                    ]
                )
            ]
        );
    }
}