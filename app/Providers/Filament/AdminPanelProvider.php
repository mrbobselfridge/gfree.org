<?php

namespace App\Providers\Filament;

use App\Support\AdminNavigationHelp;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Tables\View\TablesRenderHook;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Js;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()
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

                        .gfree-user-permission-list .fi-fo-checkbox-list-option-ctn {
                            padding-inline-start: 1.25rem;
                        }

                        .gfree-user-permission-list .fi-fo-checkbox-list-actions {
                            padding-inline-start: 1.25rem;
                        }

                        .gfree-user-permission-list .fi-fo-checkbox-list-option {
                            gap: 0.625rem;
                        }

                        .fi-sidebar-item-btn.gfree-sidebar-help-ready {
                            gap: 0.75rem;
                        }

                        .gfree-sidebar-help {
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            width: 1.375rem;
                            height: 1.375rem;
                            margin-inline-start: auto;
                            border: 1px solid rgb(217 119 6 / 0.55);
                            border-radius: 9999px;
                            color: rgb(217 119 6);
                            font-size: 0.8125rem;
                            font-weight: 700;
                            line-height: 1;
                            cursor: help;
                            flex-shrink: 0;
                        }

                        .gfree-sidebar-help:hover,
                        .gfree-sidebar-help:focus {
                            border-color: rgb(245 158 11);
                            background: rgb(245 158 11 / 0.14);
                            color: rgb(245 158 11);
                            outline: none;
                        }

                        .fi-sidebar:not(.fi-sidebar-open) .gfree-sidebar-help {
                            display: none;
                        }

                        .gfree-admin-nav-help-tooltip {
                            position: fixed;
                            z-index: 9999;
                            max-width: min(22rem, calc(100vw - 2rem));
                            padding: 0.75rem 0.875rem;
                            border-radius: 0.5rem;
                            background: rgb(39 39 42);
                            color: white;
                            box-shadow: 0 20px 45px rgb(0 0 0 / 0.28);
                            font-size: 0.875rem;
                            font-weight: 600;
                            line-height: 1.45;
                            pointer-events: none;
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
                TablesRenderHook::TOOLBAR_START,
                fn (): HtmlString => request()->is('admin/ministries')
                    ? new HtmlString(<<<'HTML'
                        <h2 class="gfree-ministry-table-toolbar-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                            Individual Ministries
                        </h2>
                    HTML)
                    : new HtmlString(''),
            )
            ->renderHook(
                TablesRenderHook::TOOLBAR_START,
                fn (): HtmlString => request()->is('admin/staff-members')
                    ? new HtmlString(<<<'HTML'
                        <h2 class="gfree-leadership-table-toolbar-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                            Individual Leaders
                        </h2>
                    HTML)
                    : new HtmlString(''),
            )
            ->renderHook(
                TablesRenderHook::TOOLBAR_START,
                fn (): HtmlString => request()->is('admin/announcements')
                    ? new HtmlString(<<<'HTML'
                        <h2 class="gfree-announcements-table-toolbar-heading text-base font-semibold leading-6 text-gray-950 dark:text-white">
                            Individual Announcements
                        </h2>
                    HTML)
                    : new HtmlString(''),
            )
            ->renderHook(
                PanelsRenderHook::SCRIPTS_AFTER,
                function (): HtmlString {
                    $descriptions = Js::from(AdminNavigationHelp::descriptions());

                    return new HtmlString(<<<HTML
                    <script>
                        (() => {
                            const descriptions = {$descriptions};
                            let tooltip = null;

                            const normalizeLabel = (value) => value.replace(/\\s+/g, ' ').trim();

                            const ensureTooltip = () => {
                                if (tooltip) {
                                    return tooltip;
                                }

                                tooltip = document.createElement('div');
                                tooltip.className = 'gfree-admin-nav-help-tooltip';
                                tooltip.hidden = true;
                                document.body.appendChild(tooltip);

                                return tooltip;
                            };

                            const hideTooltip = () => {
                                if (tooltip) {
                                    tooltip.hidden = true;
                                }
                            };

                            const showTooltip = (trigger) => {
                                const content = trigger.dataset.gfreeHelp;

                                if (! content) {
                                    return;
                                }

                                const tooltipEl = ensureTooltip();
                                tooltipEl.textContent = content;
                                tooltipEl.hidden = false;

                                const triggerRect = trigger.getBoundingClientRect();
                                const tooltipRect = tooltipEl.getBoundingClientRect();
                                const gap = 10;
                                let left = triggerRect.right + gap;
                                let top = triggerRect.top + (triggerRect.height / 2) - (tooltipRect.height / 2);

                                if ((left + tooltipRect.width) > (window.innerWidth - 16)) {
                                    left = triggerRect.left - tooltipRect.width - gap;
                                }

                                top = Math.max(16, Math.min(top, window.innerHeight - tooltipRect.height - 16));

                                tooltipEl.style.left = Math.max(16, left) + 'px';
                                tooltipEl.style.top = top + 'px';
                            };

                            const attachHelpIcons = () => {
                                document.querySelectorAll('.fi-main-sidebar .fi-sidebar-item-btn').forEach((link) => {
                                    if (link.querySelector('.gfree-sidebar-help')) {
                                        return;
                                    }

                                    const label = normalizeLabel(link.querySelector('.fi-sidebar-item-label')?.textContent ?? '');
                                    const description = descriptions[label];

                                    if (! description) {
                                        return;
                                    }

                                    const icon = document.createElement('span');
                                    icon.className = 'gfree-sidebar-help';
                                    icon.textContent = 'i';
                                    icon.setAttribute('role', 'button');
                                    icon.setAttribute('tabindex', '0');
                                    icon.setAttribute('aria-label', 'About ' + label + ': ' + description);
                                    icon.setAttribute('title', description);
                                    icon.dataset.gfreeHelp = description;

                                    icon.addEventListener('click', (event) => {
                                        event.preventDefault();
                                        event.stopPropagation();
                                    });

                                    icon.addEventListener('keydown', (event) => {
                                        if ((event.key !== 'Enter') && (event.key !== ' ')) {
                                            return;
                                        }

                                        event.preventDefault();
                                        event.stopPropagation();
                                        showTooltip(icon);
                                    });

                                    icon.addEventListener('mouseenter', () => showTooltip(icon));
                                    icon.addEventListener('mouseleave', hideTooltip);
                                    icon.addEventListener('focus', () => showTooltip(icon));
                                    icon.addEventListener('blur', hideTooltip);

                                    link.classList.add('gfree-sidebar-help-ready');
                                    link.appendChild(icon);
                                });
                            };

                            document.addEventListener('DOMContentLoaded', attachHelpIcons);
                            document.addEventListener('livewire:navigated', attachHelpIcons);
                            document.addEventListener('livewire:initialized', attachHelpIcons);
                            window.addEventListener('resize', hideTooltip);
                            window.addEventListener('scroll', hideTooltip, true);
                            window.setTimeout(attachHelpIcons, 150);
                        })();
                    </script>
                    HTML);
                },
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
