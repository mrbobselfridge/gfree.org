<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\HomeController;
use App\Models\HomepageContent;
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
            'socialLinks' => $settings?->socialLinks() ?? collect(),
        ];
    }

}
