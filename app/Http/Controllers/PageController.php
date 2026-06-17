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
            throw new NotFoundHttpException;
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

    private function viewData(Page $page): array
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
