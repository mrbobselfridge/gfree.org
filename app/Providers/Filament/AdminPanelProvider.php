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

                        .gfree-dashboard-global-controls-host {
                            position: relative;
                        }

                        .gfree-dashboard-global-controls {
                            position: absolute;
                            top: 50%;
                            inset-inline-end: 0;
                            display: flex;
                            align-items: center;
                            justify-content: flex-end;
                            gap: 0.5rem;
                            transform: translateY(-50%);
                            z-index: 3;
                        }

                        .gfree-dashboard-global-control {
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            height: 2rem;
                            border: 1px solid rgb(245 158 11 / 0.45);
                            border-radius: 0.375rem;
                            background: rgb(255 255 255);
                            color: rgb(180 83 9);
                            font-size: 0.75rem;
                            font-weight: 800;
                            line-height: 1;
                            padding-inline: 0.625rem;
                            white-space: nowrap;
                        }

                        .dark .gfree-dashboard-global-control {
                            border-color: rgb(245 158 11 / 0.5);
                            background: rgb(17 24 39);
                            color: rgb(251 191 36);
                        }

                        .gfree-dashboard-global-control:hover,
                        .gfree-dashboard-global-control:focus {
                            border-color: rgb(245 158 11);
                            background: rgb(255 251 235);
                            color: rgb(180 83 9);
                            outline: none;
                        }

                        .dark .gfree-dashboard-global-control:hover,
                        .dark .gfree-dashboard-global-control:focus {
                            border-color: rgb(245 158 11);
                            background: rgb(69 26 3 / 0.3);
                            color: rgb(251 191 36);
                        }

                        .gfree-cms-dashboard-widgets > .fi-sc {
                            display: flex !important;
                            align-items: flex-start;
                            gap: 1.5rem;
                        }

                        .gfree-dashboard-widget-column {
                            display: grid;
                            flex: 1 1 0;
                            gap: 1.5rem;
                            min-width: 0;
                        }

                        .gfree-cms-dashboard-widgets > .fi-sc > .fi-wi-widget,
                        .gfree-dashboard-widget-column > .fi-wi-widget {
                            display: block;
                            width: 100%;
                            min-width: 0;
                        }

                        .gfree-dashboard-widget {
                            transition: opacity 120ms ease, outline-color 120ms ease, transform 120ms ease;
                        }

                        .gfree-dashboard-widget .fi-section {
                            position: relative;
                            overflow: hidden;
                            border-color: rgb(229 231 235);
                            background: linear-gradient(180deg, rgb(255 255 255), rgb(249 250 251));
                            box-shadow: 0 12px 30px rgb(15 23 42 / 0.06);
                        }

                        .dark .gfree-dashboard-widget .fi-section {
                            border-color: rgb(31 41 55);
                            background: linear-gradient(180deg, rgb(24 24 27), rgb(17 24 39));
                            box-shadow: 0 18px 36px rgb(0 0 0 / 0.24);
                        }

                        .gfree-dashboard-widget-shell {
                            display: grid;
                            gap: 0.875rem;
                        }

                        .gfree-dashboard-widget-controls {
                            position: absolute;
                            top: 0.875rem;
                            inset-inline: 0.875rem;
                            display: flex;
                            align-items: center;
                            justify-content: flex-end;
                            gap: 0.5rem;
                            pointer-events: none;
                            z-index: 2;
                        }

                        .gfree-dashboard-widget-controls > * {
                            pointer-events: auto;
                        }

                        .gfree-dashboard-widget-header {
                            min-width: 0;
                            padding-top: 0;
                            padding-inline-end: 5.5rem;
                            padding-bottom: 0.875rem;
                            border-bottom: 1px solid rgb(229 231 235);
                        }

                        .dark .gfree-dashboard-widget-header {
                            border-bottom-color: rgb(55 65 81);
                        }

                        .gfree-dashboard-widget-title {
                            margin: 0;
                            color: rgb(17 24 39);
                            font-size: 1.05rem;
                            font-weight: 800;
                            line-height: 1.2;
                        }

                        .dark .gfree-dashboard-widget-title {
                            color: white;
                        }

                        .gfree-dashboard-widget-description {
                            margin-top: 0.35rem;
                            color: rgb(75 85 99);
                            font-size: 0.8125rem;
                            font-weight: 500;
                            line-height: 1.45;
                        }

                        .dark .gfree-dashboard-widget-description {
                            color: rgb(156 163 175);
                        }

                        .gfree-dashboard-widget-action,
                        .gfree-dashboard-widget-count,
                        .gfree-dashboard-widget-drag-handle,
                        .gfree-dashboard-widget-collapse {
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            border: 1px solid rgb(209 213 219);
                            border-radius: 0.375rem;
                            background: rgb(255 255 255);
                            color: rgb(55 65 81);
                            font-size: 0.75rem;
                            font-weight: 800;
                            line-height: 1;
                            text-decoration: none;
                            white-space: nowrap;
                        }

                        .gfree-dashboard-widget-action {
                            height: 2rem;
                            padding-inline: 0.625rem;
                        }

                        .gfree-dashboard-widget-count {
                            width: 2rem;
                            height: 2rem;
                            padding: 0;
                            color: rgb(75 85 99);
                            cursor: default;
                        }

                        .gfree-dashboard-widget-drag-handle,
                        .gfree-dashboard-widget-collapse {
                            width: 2rem;
                            height: 2rem;
                            padding: 0;
                        }

                        .gfree-dashboard-widget-control-icon {
                            width: 1rem;
                            height: 1rem;
                            flex-shrink: 0;
                            pointer-events: none;
                        }

                        .gfree-dashboard-widget-collapse-icon-collapsed {
                            display: none;
                        }

                        .gfree-dashboard-widget.gfree-dashboard-widget-collapsed .gfree-dashboard-widget-collapse-icon-expanded {
                            display: none;
                        }

                        .gfree-dashboard-widget.gfree-dashboard-widget-collapsed .gfree-dashboard-widget-collapse-icon-collapsed {
                            display: block;
                        }

                        .dark .gfree-dashboard-widget-action,
                        .dark .gfree-dashboard-widget-count,
                        .dark .gfree-dashboard-widget-drag-handle,
                        .dark .gfree-dashboard-widget-collapse {
                            border-color: rgb(75 85 99);
                            background: rgb(17 24 39);
                            color: rgb(229 231 235);
                        }

                        .gfree-dashboard-widget-action {
                            border-color: rgb(245 158 11 / 0.45);
                            color: rgb(180 83 9);
                        }

                        .dark .gfree-dashboard-widget-action {
                            border-color: rgb(245 158 11 / 0.5);
                            color: rgb(251 191 36);
                        }

                        .gfree-dashboard-widget-drag-handle {
                            cursor: grab;
                            touch-action: none;
                        }

                        .gfree-dashboard-widget-drag-handle:active {
                            cursor: grabbing;
                        }

                        .gfree-dashboard-widget-action:hover,
                        .gfree-dashboard-widget-action:focus,
                        .gfree-dashboard-widget-drag-handle:hover,
                        .gfree-dashboard-widget-drag-handle:focus,
                        .gfree-dashboard-widget-collapse:hover,
                        .gfree-dashboard-widget-collapse:focus {
                            border-color: rgb(245 158 11);
                            background: rgb(255 251 235);
                            color: rgb(180 83 9);
                            outline: none;
                        }

                        .dark .gfree-dashboard-widget-action:hover,
                        .dark .gfree-dashboard-widget-action:focus,
                        .dark .gfree-dashboard-widget-drag-handle:hover,
                        .dark .gfree-dashboard-widget-drag-handle:focus,
                        .dark .gfree-dashboard-widget-collapse:hover,
                        .dark .gfree-dashboard-widget-collapse:focus {
                            border-color: rgb(245 158 11);
                            background: rgb(69 26 3 / 0.3);
                            color: rgb(251 191 36);
                        }

                        .gfree-dashboard-widget-body {
                            display: grid;
                            gap: 0.625rem;
                            margin-top: 1rem;
                        }

                        .gfree-dashboard-widget-row {
                            display: flex;
                            align-items: center;
                            gap: 0.875rem;
                            min-width: 0;
                            border: 1px solid rgb(229 231 235);
                            border-radius: 0.5rem;
                            background: rgb(255 255 255 / 0.78);
                            padding: 0.75rem;
                        }

                        .gfree-dashboard-widget-row > .min-w-0.flex-1 {
                            flex: 1 1 auto;
                            min-width: 0;
                        }

                        .gfree-dashboard-widget-row-status {
                            margin-inline-start: auto;
                            flex-shrink: 0;
                        }

                        .dark .gfree-dashboard-widget-row {
                            border-color: rgb(55 65 81);
                            background: rgb(3 7 18 / 0.38);
                        }

                        .gfree-dashboard-widget-row-image {
                            width: 4.75rem;
                            height: 3.25rem;
                            flex-shrink: 0;
                            border-radius: 0.5rem;
                            object-fit: cover;
                            box-shadow: inset 0 0 0 1px rgb(15 23 42 / 0.1);
                        }

                        .gfree-dashboard-widget-row-image-link {
                            display: block;
                            flex-shrink: 0;
                            border-radius: 0.5rem;
                            outline: none;
                        }

                        .gfree-dashboard-widget-row-image-link:hover .gfree-dashboard-widget-row-image,
                        .gfree-dashboard-widget-row-image-link:focus .gfree-dashboard-widget-row-image {
                            box-shadow: 0 0 0 2px rgb(245 158 11 / 0.75);
                        }

                        .gfree-dashboard-widget-type {
                            display: inline-flex;
                            align-items: center;
                            max-width: 100%;
                            color: rgb(146 64 14);
                            font-size: 0.825rem;
                            font-weight: 800;
                            line-height: 1;
                            overflow-wrap: anywhere;
                        }

                        .dark .gfree-dashboard-widget-type {
                            color: rgb(251 191 36);
                        }

                        .gfree-dashboard-widget-row-title {
                            display: block;
                            color: rgb(17 24 39);
                            font-size: 0.875rem;
                            font-weight: 750;
                            line-height: 1.35;
                            text-decoration: none;
                            overflow-wrap: anywhere;
                            white-space: normal;
                        }

                        .dark .gfree-dashboard-widget-row-title {
                            color: white;
                        }

                        .gfree-dashboard-widget-row-title:hover,
                        .gfree-dashboard-widget-row-title:focus {
                            color: rgb(217 119 6);
                            outline: none;
                        }

                        .dark .gfree-dashboard-widget-row-title:hover,
                        .dark .gfree-dashboard-widget-row-title:focus {
                            color: rgb(251 191 36);
                        }

                        .gfree-dashboard-widget-row-meta {
                            margin-top: 0.25rem;
                            color: rgb(107 114 128);
                            font-size: 0.75rem;
                            font-weight: 500;
                            line-height: 1.35;
                            overflow-wrap: anywhere;
                            white-space: normal;
                        }

                        .dark .gfree-dashboard-widget-row-meta {
                            color: rgb(156 163 175);
                        }

                        .gfree-dashboard-widget-empty {
                            border: 1px dashed rgb(209 213 219);
                            border-radius: 0.5rem;
                            color: rgb(107 114 128);
                            font-size: 0.875rem;
                            font-weight: 600;
                            line-height: 1.45;
                            padding: 0.875rem;
                        }

                        .dark .gfree-dashboard-widget-empty {
                            border-color: rgb(75 85 99);
                            color: rgb(156 163 175);
                        }

                        .gfree-dashboard-widget.gfree-dashboard-widget-dragging {
                            opacity: 0.92;
                            pointer-events: none;
                            transform: rotate(0.35deg);
                        }

                        .gfree-dashboard-widget-placeholder {
                            display: block;
                            width: 100%;
                            border: 2px dashed rgb(245 158 11);
                            border-radius: 0.75rem;
                            background: rgb(245 158 11 / 0.09);
                        }

                        .gfree-dashboard-widget.gfree-dashboard-widget-collapsed [data-gfree-dashboard-widget-body],
                        .gfree-dashboard-widget.gfree-dashboard-widget-collapsed [data-gfree-dashboard-widget-description] {
                            display: none;
                        }

                        @media (max-width: 640px) {
                            .gfree-dashboard-global-controls {
                                position: static;
                                justify-content: flex-start;
                                margin-bottom: 1rem;
                                transform: none;
                            }

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

                                return Array.from(container.querySelectorAll('.gfree-dashboard-widget[data-gfree-dashboard-widget]'));
                            };

                            const dashboardColumnCount = () => {
                                if (window.matchMedia('(min-width: 1536px)').matches) {
                                    return 3;
                                }

                                if (window.matchMedia('(min-width: 1024px)').matches) {
                                    return 2;
                                }

                                return 1;
                            };

                            const dashboardColumns = () => {
                                const container = dashboardContainer();

                                if (! container) {
                                    return [];
                                }

                                return Array.from(container.querySelectorAll(':scope > .gfree-dashboard-widget-column'));
                            };

                            const shortestColumn = (columns) => columns.reduce(
                                (shortest, column) => column.getBoundingClientRect().height < shortest.getBoundingClientRect().height ? column : shortest,
                                columns[0],
                            );

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

                            const setAllCollapsed = (collapsed) => {
                                const state = readState();

                                dashboardWidgets().forEach((widget) => {
                                    const key = widget.dataset.gfreeDashboardWidget;

                                    state.collapsed[key] = collapsed;
                                    applyCollapsedState(widget, collapsed);
                                });

                                writeState(state);
                            };

                            const ensureDashboardGlobalControls = () => {
                                const dashboard = document.querySelector('.gfree-cms-dashboard');
                                const host = dashboard?.querySelector('.fi-header') ?? dashboard;

                                if (! host || host.querySelector('.gfree-dashboard-global-controls')) {
                                    return;
                                }

                                host.classList.add('gfree-dashboard-global-controls-host');

                                const controls = document.createElement('div');
                                controls.className = 'gfree-dashboard-global-controls';

                                const expandButton = document.createElement('button');
                                expandButton.type = 'button';
                                expandButton.className = 'gfree-dashboard-global-control';
                                expandButton.textContent = 'Expand All';
                                expandButton.setAttribute('aria-label', 'Expand all dashboard boxes');
                                expandButton.addEventListener('click', () => setAllCollapsed(false));

                                const collapseButton = document.createElement('button');
                                collapseButton.type = 'button';
                                collapseButton.className = 'gfree-dashboard-global-control';
                                collapseButton.textContent = 'Collapse All';
                                collapseButton.setAttribute('aria-label', 'Collapse all dashboard boxes');
                                collapseButton.addEventListener('click', () => setAllCollapsed(true));

                                controls.append(expandButton, collapseButton);
                                host.append(controls);
                            };

                            const applyCollapsedState = (widget, collapsed) => {
                                const heading = widgetHeading(widget);
                                const button = widget.querySelector('[data-gfree-dashboard-widget-collapse]');

                                widget.classList.toggle('gfree-dashboard-widget-collapsed', collapsed);

                                if (! button) {
                                    return;
                                }

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

                                const columns = Array.from({ length: dashboardColumnCount() }, () => {
                                    const column = document.createElement('div');
                                    column.className = 'gfree-dashboard-widget-column';

                                    return column;
                                });

                                container.replaceChildren(...columns);

                                [...ordered, ...byKey.values()].forEach((widget) => {
                                    shortestColumn(columns).appendChild(widget);
                                });
                            };

                            const saveCurrentOrder = () => {
                                const state = readState();
                                const columns = dashboardColumns();
                                const widgets = columns.length
                                    ? columns.flatMap((column) => Array.from(column.querySelectorAll(':scope > .gfree-dashboard-widget[data-gfree-dashboard-widget]')))
                                    : dashboardWidgets();

                                state.order = widgets.map((widget) => widget.dataset.gfreeDashboardWidget);
                                writeState(state);
                            };

                            const clearDragStyles = (widget) => {
                                widget.style.position = '';
                                widget.style.inset = '';
                                widget.style.left = '';
                                widget.style.top = '';
                                widget.style.width = '';
                                widget.style.zIndex = '';
                                widget.style.margin = '';
                                widget.style.transform = '';
                                widget.classList.remove('gfree-dashboard-widget-dragging');
                            };

                            const startWidgetDrag = (event, widget) => {
                                if ((event.button !== undefined) && (event.button !== 0)) {
                                    return;
                                }

                                const container = dashboardContainer();

                                if (! container) {
                                    return;
                                }

                                event.preventDefault();
                                event.stopPropagation();

                                const rect = widget.getBoundingClientRect();
                                const placeholder = document.createElement('div');
                                placeholder.className = 'gfree-dashboard-widget-placeholder';
                                placeholder.style.height = rect.height + 'px';
                                widget.after(placeholder);

                                const offsetX = event.clientX - rect.left;
                                const offsetY = event.clientY - rect.top;

                                widget.classList.add('gfree-dashboard-widget-dragging');
                                widget.style.position = 'fixed';
                                widget.style.left = rect.left + 'px';
                                widget.style.top = rect.top + 'px';
                                widget.style.width = rect.width + 'px';
                                widget.style.zIndex = '9999';
                                widget.style.margin = '0';
                                document.body.appendChild(widget);

                                const moveWidget = (clientX, clientY) => {
                                    widget.style.left = (clientX - offsetX) + 'px';
                                    widget.style.top = (clientY - offsetY) + 'px';
                                };

                                const movePlaceholder = (clientX, clientY) => {
                                    const columns = dashboardColumns();

                                    if (! columns.length) {
                                        return;
                                    }

                                    const column = columns.find((candidate) => {
                                        const rect = candidate.getBoundingClientRect();

                                        return clientX >= rect.left && clientX <= rect.right;
                                    }) ?? columns.reduce((closest, candidate) => {
                                        const closestRect = closest.getBoundingClientRect();
                                        const candidateRect = candidate.getBoundingClientRect();
                                        const closestDistance = Math.abs(clientX - (closestRect.left + (closestRect.width / 2)));
                                        const candidateDistance = Math.abs(clientX - (candidateRect.left + (candidateRect.width / 2)));

                                        return candidateDistance < closestDistance ? candidate : closest;
                                    }, columns[0]);

                                    const targets = Array.from(column.querySelectorAll(':scope > .gfree-dashboard-widget[data-gfree-dashboard-widget]'))
                                        .filter((candidate) => candidate !== widget);

                                    const target = targets.find((candidate) => {
                                        const rect = candidate.getBoundingClientRect();

                                        return clientY < rect.top + (rect.height / 2);
                                    });

                                    if (target) {
                                        target.before(placeholder);
                                    } else {
                                        column.appendChild(placeholder);
                                    }
                                };

                                const onPointerMove = (moveEvent) => {
                                    moveWidget(moveEvent.clientX, moveEvent.clientY);
                                    movePlaceholder(moveEvent.clientX, moveEvent.clientY);
                                };

                                const onPointerUp = () => {
                                    document.removeEventListener('pointermove', onPointerMove);
                                    document.removeEventListener('pointerup', onPointerUp);
                                    document.removeEventListener('pointercancel', onPointerUp);

                                    placeholder.replaceWith(widget);
                                    clearDragStyles(widget);
                                    saveCurrentOrder();
                                };

                                moveWidget(event.clientX, event.clientY);
                                document.addEventListener('pointermove', onPointerMove);
                                document.addEventListener('pointerup', onPointerUp);
                                document.addEventListener('pointercancel', onPointerUp);
                            };

                            const initializeDashboardWidgets = () => {
                                ensureDashboardGlobalControls();
                                applySavedOrder();

                                const state = readState();

                                dashboardWidgets().forEach((widget) => {
                                    const key = widget.dataset.gfreeDashboardWidget;
                                    const heading = widgetHeading(widget);
                                    const collapseButton = widget.querySelector('[data-gfree-dashboard-widget-collapse]');
                                    const dragHandle = widget.querySelector('[data-gfree-dashboard-widget-drag-handle]');

                                    applyCollapsedState(widget, Boolean(state.collapsed[key]));

                                    if (widget.dataset.gfreeDashboardReady === 'true') {
                                        return;
                                    }

                                    widget.dataset.gfreeDashboardReady = 'true';

                                    if (dragHandle) {
                                        dragHandle.addEventListener('pointerdown', (event) => startWidgetDrag(event, widget));
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

                                    if (dragHandle) {
                                        dragHandle.setAttribute('title', 'Move ' + heading);
                                        dragHandle.setAttribute('aria-label', 'Move ' + heading);
                                    }
                                });
                            };

                            let resizeTimer = null;
                            const initializeDashboardWidgetsAfterResize = () => {
                                window.clearTimeout(resizeTimer);
                                resizeTimer = window.setTimeout(initializeDashboardWidgets, 150);
                            };

                            document.addEventListener('DOMContentLoaded', initializeDashboardWidgets);
                            document.addEventListener('livewire:navigated', initializeDashboardWidgets);
                            document.addEventListener('livewire:initialized', initializeDashboardWidgets);
                            window.addEventListener('resize', initializeDashboardWidgetsAfterResize);
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
