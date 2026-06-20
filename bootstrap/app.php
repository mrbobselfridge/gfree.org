<?php

use App\Http\Middleware\TrackPublicPageView;
use App\Models\Page;
use App\Support\PublicPageViewData;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->appendToGroup('web', TrackPublicPageView::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            if ($request->expectsJson() || ! in_array($request->method(), ['GET', 'HEAD'], true)) {
                return null;
            }

            try {
                $page = Page::query()
                    ->where('slug', '404')
                    ->where('is_redirect', false)
                    ->active()
                    ->first();

                if (! $page) {
                    return null;
                }

                return response()
                    ->view('pages.show', app(PublicPageViewData::class)->forPage($page), 404);
            } catch (Throwable) {
                return null;
            }
        });
    })->create();
