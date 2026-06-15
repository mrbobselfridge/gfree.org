<?php

namespace App\Http\Controllers;

use App\Models\NavigationLink;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Support\ContentBlocks;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PageController extends Controller
{
    public function __invoke(string $slug): View|RedirectResponse
    {
        $page = Page::query()
            ->where('slug', $slug)
            ->active()
            ->first();

        if (! $page) {
            return $this->relatedContentListing($slug);
        }

        if ($page->isRedirect()) {
            $redirectUrl = trim((string) $page->redirect_url);
            $statusCode = (int) $page->redirect_status_code;

            return Str::startsWith($redirectUrl, ['http://', 'https://'])
                ? redirect()->away($redirectUrl, $statusCode)
                : redirect($redirectUrl, $statusCode);
        }

        return view('pages.show', $this->viewData($page));
    }

    private function relatedContentListing(string $slug): View
    {
        $listing = ContentBlocks::relatedContentListing($slug);

        if (! $listing) {
            throw new NotFoundHttpException;
        }

        return view('pages.related-content-listing', [
            ...$this->viewData($listing['page']),
            'data' => $listing['data'],
        ]);
    }

    private function viewData(Page $page): array
    {
        $settings = SiteSetting::query()->first();
        $defaults = config('twyxtco.homepage');
        $navigationLinks = NavigationLink::topLevelHeaderLinks();

        return [
            'settings' => $settings,
            'page' => $page,
            'contentBlocks' => ContentBlocks::prepare($page->content_blocks, $settings, page: $page),
            'heroImageUrl' => ContentBlocks::imageUrl($page->hero_image_path),
            'headerLinks' => $navigationLinks->isNotEmpty() ? $navigationLinks : collect($defaults['navigation']),
            'socialLinks' => $this->socialLinks($settings),
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
