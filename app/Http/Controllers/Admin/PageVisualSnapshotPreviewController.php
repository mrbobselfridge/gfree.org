<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HomeController;
use App\Models\HomepageContent;
use App\Models\Ministry;
use App\Models\NavigationLink;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Support\ContentBlocks;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PageVisualSnapshotPreviewController extends Controller
{
    public function __invoke(Request $request, string $type, ?int $record = null): View
    {
        return match ($type) {
            'page' => $this->page(Page::query()->findOrFail($record)),
            'ministry' => $this->ministry(Ministry::query()->findOrFail($record)),
            'homepage' => $this->homepage(HomepageContent::query()->firstOrCreate([])),
            default => abort(404),
        };
    }

    private function page(Page $page): View
    {
        $settings = SiteSetting::query()->first();

        return view('pages.show', [
            ...$this->sharedViewData($settings),
            'settings' => $settings,
            'page' => $page,
            'contentBlocks' => ContentBlocks::prepare($page->content_blocks, $settings, page: $page),
            'heroImageUrl' => ContentBlocks::imageUrl($page->hero_image_path)
                ?: ContentBlocks::imageUrl($settings?->default_page_header_image_path),
        ]);
    }

    private function ministry(Ministry $ministry): View
    {
        $settings = SiteSetting::query()->first();

        return view('ministries.show', [
            ...$this->sharedViewData($settings),
            'settings' => $settings,
            'ministry' => $ministry,
            'contentBlocks' => ContentBlocks::prepare($ministry->content_blocks, $settings),
            'heroImageUrl' => ContentBlocks::imageUrl($ministry->hero_image_path) ?: $this->listingImageUrl('ministry', $settings),
            'detailItems' => collect([
                ['label' => 'When', 'value' => $ministry->meeting_time],
                ['label' => 'Where', 'value' => $ministry->location],
            ])->filter(fn (array $item): bool => filled($item['value'])),
        ]);
    }

    private function homepage(HomepageContent $content): View
    {
        $controller = app(HomeController::class);

        return $controller();
    }

    /**
     * @return array<string, mixed>
     */
    private function sharedViewData(?SiteSetting $settings): array
    {
        $defaults = config('twyxtco.homepage');
        $navigationLinks = NavigationLink::topLevelHeaderLinks();

        return [
            'headerLinks' => $navigationLinks->isNotEmpty() ? $navigationLinks : collect($defaults['navigation']),
            'socialLinks' => collect([
                ['label' => 'Facebook', 'url' => $settings?->facebook_url],
                ['label' => 'Instagram', 'url' => $settings?->instagram_url],
                ['label' => 'YouTube', 'url' => $settings?->youtube_url],
            ])->filter(fn (array $link): bool => filled($link['url'])),
        ];
    }

    private function listingImageUrl(string $prefix, ?SiteSetting $settings): ?string
    {
        return ContentBlocks::imageUrl(data_get($settings, "{$prefix}_image_path"));
    }
}
