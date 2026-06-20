<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Support\PublicPageViewData;
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

        return view('pages.show', app(PublicPageViewData::class)->forPage($page));
    }
}
