<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Illuminate\Support\HtmlString;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): HtmlString => new HtmlString(<<<'HTML'
                    <style>
                        .gfree-content-block-builder-field {
                            position: relative;
                        }

                        .gfree-content-block-builder-field > .fi-fo-field-label-col {
                            padding-inline-end: 18rem;
                        }

                        .gfree-content-block-builder-field .fi-fo-field-label-content {
                            color: var(--gray-950);
                            font-size: 1.125rem;
                            font-weight: 750;
                            line-height: 1.25;
                        }

                        .dark .gfree-content-block-builder-field .fi-fo-field-label-content {
                            color: white;
                        }

                        .gfree-content-block-builder-field .fi-fo-builder > .fi-fo-builder-actions {
                            position: absolute;
                            top: 0;
                            inset-inline-end: 0;
                            align-items: center;
                            gap: 0;
                        }

                        .gfree-content-block-builder-field .fi-fo-builder > .fi-fo-builder-actions > span + span {
                            margin-inline-start: 1.25rem;
                            padding-inline-start: 1.25rem;
                            border-inline-start: 1px solid var(--gray-300);
                        }

                        .dark .gfree-content-block-builder-field .fi-fo-builder > .fi-fo-builder-actions > span + span {
                            border-inline-start-color: var(--gray-700);
                        }

                        @media (max-width: 640px) {
                            .gfree-content-block-builder-field > .fi-fo-field-label-col {
                                padding-inline-end: 0;
                            }

                            .gfree-content-block-builder-field .fi-fo-builder > .fi-fo-builder-actions {
                                position: static;
                                justify-content: flex-start;
                                margin-bottom: 0.75rem;
                            }
                        }
                    </style>
                HTML),
            )
            ->renderHook(
                PanelsRenderHook::SCRIPTS_AFTER,
                fn (): HtmlString => new HtmlString(<<<'HTML'
                    <script>
                        document.addEventListener('gfree-focus-first-form-field', () => {
                            window.setTimeout(() => {
                                document
                                    .querySelector('.fi-page form input:not([type="hidden"]):not([disabled]), .fi-page form textarea:not([disabled]), .fi-page form select:not([disabled]), .fi-page form [contenteditable="true"]')
                                    ?.focus()
                            }, 75)
                        })
                    </script>
                HTML),
            )
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
