<?php

namespace App\Providers\Filament;

use App\Filament\Admin\CmsDashboard;
use App\Support\AdminNavigationHelp;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Tables\View\TablesRenderHook;
use Filament\View\PanelsRenderHook;
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

                        .gfree-cms-dashboard-widgets > .fi-sc {
                            display: block !important;
                            column-count: 1;
                            column-gap: 1.5rem;
                        }

                        .gfree-cms-dashboard-widgets > .fi-sc > .fi-wi-widget {
                            display: inline-block;
                            width: 100%;
                            margin-bottom: 1.5rem;
                            break-inside: avoid;
                            page-break-inside: avoid;
                        }

                        .gfree-dashboard-widget {
                            transition: opacity 120ms ease, outline-color 120ms ease;
                        }

                        .gfree-dashboard-widget-drag-handle,
                        .gfree-dashboard-widget-collapse {
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            min-height: 1.75rem;
                            border: 1px solid rgb(209 213 219);
                            border-radius: 0.375rem;
                            background: white;
                            color: rgb(55 65 81);
                            font-size: 0.75rem;
                            font-weight: 700;
                            line-height: 1;
                            padding: 0.35rem 0.5rem;
                        }

                        .dark .gfree-dashboard-widget-drag-handle,
                        .dark .gfree-dashboard-widget-collapse {
                            border-color: rgb(75 85 99);
                            background: rgb(17 24 39);
                            color: rgb(229 231 235);
                        }

                        .gfree-dashboard-widget-drag-handle {
                            cursor: grab;
                        }

                        .gfree-dashboard-widget-drag-handle:active {
                            cursor: grabbing;
                        }

                        .gfree-dashboard-widget-drag-handle:hover,
                        .gfree-dashboard-widget-drag-handle:focus,
                        .gfree-dashboard-widget-collapse:hover,
                        .gfree-dashboard-widget-collapse:focus {
                            border-color: rgb(245 158 11);
                            color: rgb(217 119 6);
                            outline: none;
                        }

                        .dark .gfree-dashboard-widget-drag-handle:hover,
                        .dark .gfree-dashboard-widget-drag-handle:focus,
                        .dark .gfree-dashboard-widget-collapse:hover,
                        .dark .gfree-dashboard-widget-collapse:focus {
                            border-color: rgb(245 158 11);
                            color: rgb(251 191 36);
                        }

                        .gfree-dashboard-widget.gfree-dashboard-widget-dragging {
                            opacity: 0.5;
                        }

                        .gfree-dashboard-widget.gfree-dashboard-widget-drag-over {
                            outline: 2px solid rgb(245 158 11);
                            outline-offset: 4px;
                        }

                        .gfree-dashboard-widget.gfree-dashboard-widget-collapsed [data-gfree-dashboard-widget-body],
                        .gfree-dashboard-widget.gfree-dashboard-widget-collapsed [data-gfree-dashboard-widget-description] {
                            display: none;
                        }

                        @media (min-width: 1024px) {
                            .gfree-cms-dashboard-widgets > .fi-sc {
                                column-count: 2;
                            }
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
            ->renderHook(
                PanelsRenderHook::SCRIPTS_AFTER,
                fn (): HtmlString => new HtmlString(<<<'HTML'
                    <script>
                        (() => {
                            const storageKey = 'gfree.admin.dashboard.widgets.v1';

                            const dashboardContainer = () => document.querySelector('.gfree-cms-dashboard-widgets > .fi-sc');

                            const dashboardWidgets = () => {
                                const container = dashboardContainer();

                                if (! container) {
                                    return [];
                                }

                                return Array.from(container.querySelectorAll(':scope > .gfree-dashboard-widget[data-gfree-dashboard-widget]'));
                            };

                            const readState = () => {
                                try {
                                    const state = JSON.parse(window.localStorage.getItem(storageKey) || '{}');

                                    return {
                                        order: Array.isArray(state.order) ? state.order : [],
                                        collapsed: state.collapsed && (typeof state.collapsed === 'object') ? state.collapsed : {},
                                    };
                                } catch (error) {
                                    return { order: [], collapsed: {} };
                                }
                            };

                            const writeState = (state) => {
                                try {
                                    window.localStorage.setItem(storageKey, JSON.stringify(state));
                                } catch (error) {
                                    // Browser storage may be unavailable; dashboard controls can still work for this page view.
                                }
                            };

                            const widgetHeading = (widget) => widget.querySelector('h2')?.textContent?.replace(/\s+/g, ' ').trim() || 'dashboard box';

                            const applyCollapsedState = (widget, collapsed) => {
                                const heading = widgetHeading(widget);
                                const button = widget.querySelector('[data-gfree-dashboard-widget-collapse]');

                                widget.classList.toggle('gfree-dashboard-widget-collapsed', collapsed);

                                if (! button) {
                                    return;
                                }

                                button.textContent = collapsed ? 'Expand' : 'Collapse';
                                button.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
                                button.setAttribute('aria-label', (collapsed ? 'Expand ' : 'Collapse ') + heading);
                                button.setAttribute('title', (collapsed ? 'Expand ' : 'Collapse ') + heading);
                            };

                            const applySavedOrder = () => {
                                const container = dashboardContainer();

                                if (! container) {
                                    return;
                                }

                                const state = readState();
                                const widgets = dashboardWidgets();
                                const byKey = new Map(widgets.map((widget) => [widget.dataset.gfreeDashboardWidget, widget]));
                                const ordered = [];

                                state.order.forEach((key) => {
                                    if (byKey.has(key)) {
                                        ordered.push(byKey.get(key));
                                        byKey.delete(key);
                                    }
                                });

                                [...ordered, ...byKey.values()].forEach((widget) => container.appendChild(widget));
                            };

                            const saveCurrentOrder = () => {
                                const state = readState();
                                state.order = dashboardWidgets().map((widget) => widget.dataset.gfreeDashboardWidget);
                                writeState(state);
                            };

                            const initializeDashboardWidgets = () => {
                                applySavedOrder();

                                const state = readState();
                                let draggedWidget = null;

                                dashboardWidgets().forEach((widget) => {
                                    const key = widget.dataset.gfreeDashboardWidget;
                                    const heading = widgetHeading(widget);
                                    const collapseButton = widget.querySelector('[data-gfree-dashboard-widget-collapse]');
                                    const dragHandle = widget.querySelector('.gfree-dashboard-widget-drag-handle');

                                    applyCollapsedState(widget, Boolean(state.collapsed[key]));

                                    if (widget.dataset.gfreeDashboardReady === 'true') {
                                        return;
                                    }

                                    widget.dataset.gfreeDashboardReady = 'true';
                                    widget.draggable = false;

                                    if (dragHandle) {
                                        dragHandle.addEventListener('mousedown', () => {
                                            widget.draggable = true;
                                        });

                                        dragHandle.addEventListener('mouseup', () => {
                                            widget.draggable = false;
                                        });

                                        dragHandle.addEventListener('touchstart', () => {
                                            widget.draggable = true;
                                        }, { passive: true });
                                    }

                                    if (collapseButton) {
                                        collapseButton.addEventListener('click', () => {
                                            const nextState = readState();
                                            const shouldCollapse = ! widget.classList.contains('gfree-dashboard-widget-collapsed');

                                            nextState.collapsed[key] = shouldCollapse;
                                            writeState(nextState);
                                            applyCollapsedState(widget, shouldCollapse);
                                        });
                                    }

                                    widget.addEventListener('dragstart', (event) => {
                                        if (! event.target.closest('.gfree-dashboard-widget-drag-handle')) {
                                            event.preventDefault();
                                            return;
                                        }

                                        draggedWidget = widget;
                                        widget.classList.add('gfree-dashboard-widget-dragging');
                                        event.dataTransfer.effectAllowed = 'move';
                                        event.dataTransfer.setData('text/plain', key);
                                    });

                                    widget.addEventListener('dragend', () => {
                                        widget.draggable = false;
                                        widget.classList.remove('gfree-dashboard-widget-dragging');
                                        document.querySelectorAll('.gfree-dashboard-widget-drag-over').forEach((target) => {
                                            target.classList.remove('gfree-dashboard-widget-drag-over');
                                        });
                                        draggedWidget = null;
                                        saveCurrentOrder();
                                    });

                                    widget.addEventListener('dragover', (event) => {
                                        if (! draggedWidget || (draggedWidget === widget)) {
                                            return;
                                        }

                                        event.preventDefault();
                                        event.dataTransfer.dropEffect = 'move';
                                        widget.classList.add('gfree-dashboard-widget-drag-over');
                                    });

                                    widget.addEventListener('dragleave', () => {
                                        widget.classList.remove('gfree-dashboard-widget-drag-over');
                                    });

                                    widget.addEventListener('drop', (event) => {
                                        if (! draggedWidget || (draggedWidget === widget)) {
                                            return;
                                        }

                                        event.preventDefault();
                                        widget.classList.remove('gfree-dashboard-widget-drag-over');

                                        const container = dashboardContainer();
                                        const rect = widget.getBoundingClientRect();
                                        const shouldPlaceAfter = event.clientY > (rect.top + (rect.height / 2));

                                        if (shouldPlaceAfter) {
                                            container.insertBefore(draggedWidget, widget.nextSibling);
                                        } else {
                                            container.insertBefore(draggedWidget, widget);
                                        }

                                        saveCurrentOrder();
                                    });

                                    if (dragHandle) {
                                        dragHandle.setAttribute('title', 'Move ' + heading);
                                        dragHandle.setAttribute('aria-label', 'Move ' + heading);
                                    }
                                });
                            };

                            document.addEventListener('DOMContentLoaded', initializeDashboardWidgets);
                            document.addEventListener('livewire:navigated', initializeDashboardWidgets);
                            document.addEventListener('livewire:initialized', initializeDashboardWidgets);
                            window.setTimeout(initializeDashboardWidgets, 150);
                        })();
                    </script>
                HTML),
            )
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages')
            ->pages([
                CmsDashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\Filament\Admin\Widgets')
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
