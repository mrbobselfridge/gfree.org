<?php

namespace App\Support;

use App\Models\NavigationLink;
use App\Models\Page;
use App\Models\SiteAlert;
use App\Models\SiteSetting;

class PublicPageViewData
{
    public function forPage(Page $page): array
    {
        $settings = SiteSetting::query()->first();
        $defaults = config('twyxtco.homepage');
        $navigationLinks = NavigationLink::topLevelHeaderLinks();
        $utilityLinks = NavigationLink::topLevelUtilityLinks();

        return [
            'settings' => $settings,
            'page' => $page,
            'contentBlocks' => ContentBlocks::prepare($page->content_blocks, $settings, page: $page),
            'heroImageUrl' => ContentBlocks::imageUrl($page->hero_image_path)
                ?: ContentBlocks::imageUrl($settings?->default_page_header_image_path),
            'headerLinks' => $navigationLinks->isNotEmpty() ? $navigationLinks : collect($defaults['navigation']),
            'utilityLinks' => $utilityLinks,
            'utilitySocialLinks' => $settings?->utilitySocialLinks() ?? collect(),
            'siteAlerts' => SiteAlert::query()
                ->active()
                ->publicOrder()
                ->get(),
            'socialLinks' => $settings?->footerSocialLinks() ?? collect(),
            'childPageNavigation' => $this->childPageNavigation($page),
        ];
    }

    private function childPageNavigation(Page $page): array
    {
        $parent = filled($page->parent_page_id)
            ? Page::query()
                ->whereKey($page->parent_page_id)
                ->active()
                ->first()
            : null;

        if (! $parent) {
            return [];
        }

        return [
            'parent_url' => $parent->publicUrl(),
            'parent_label' => "View {$parent->title}",
        ];
    }

}
