<?php

namespace App\Http\Controllers;

use App\Models\NavigationLink;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Support\ContentBlocks;
use Illuminate\Contracts\View\View;

class PageController extends Controller
{
    public function __invoke(string $slug): View
    {
        $page = Page::query()
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        $settings = SiteSetting::query()->first();
        $defaults = config('twyxtco.homepage');
        $navigationLinks = NavigationLink::query()
            ->topLevelHeader()
            ->limit(10)
            ->get();

        return view('pages.show', [
            'settings' => $settings,
            'page' => $page,
            'contentBlocks' => ContentBlocks::prepare($page->content_blocks, $settings, ContentBlocks::featuredAnnouncementUpdates()),
            'heroImageUrl' => ContentBlocks::imageUrl($page->hero_image_path),
            'headerLinks' => $navigationLinks->isNotEmpty() ? $navigationLinks : collect($defaults['navigation']),
            'socialLinks' => $this->socialLinks($settings),
        ]);
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
