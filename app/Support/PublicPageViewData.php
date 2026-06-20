<?php

namespace App\Support;

use App\Models\NavigationLink;
use App\Models\Page;
use App\Models\SiteSetting;

class PublicPageViewData
{
    public function forPage(Page $page): array
    {
        $settings = SiteSetting::query()->first();
        $defaults = config('twyxtco.homepage');
        $navigationLinks = NavigationLink::topLevelHeaderLinks();

        return [
            'settings' => $settings,
            'page' => $page,
            'contentBlocks' => ContentBlocks::prepare($page->content_blocks, $settings, page: $page),
            'heroImageUrl' => ContentBlocks::imageUrl($page->hero_image_path)
                ?: ContentBlocks::imageUrl($settings?->default_page_header_image_path),
            'headerLinks' => $navigationLinks->isNotEmpty() ? $navigationLinks : collect($defaults['navigation']),
            'socialLinks' => $this->socialLinks($settings),
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

    private function socialLinks(?SiteSetting $settings)
    {
        return collect([
            ['label' => 'Facebook', 'url' => $settings?->facebook_url],
            ['label' => 'Instagram', 'url' => $settings?->instagram_url],
            ['label' => 'YouTube', 'url' => $settings?->youtube_url],
        ])->filter(fn (array $link) => filled($link['url']));
    }
}
