<?php

namespace App\Providers\Filament;

use App\Filament\Admin\CmsDashboard;
use App\Models\SiteSetting;
use App\Support\AdminNavigationHelp;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Schema;
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
            ->brandName(fn (): string => $this->brandName())
            ->login()
            ->passwordReset()
            ->sidebarCollapsibleOnDesktop()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->navigationGroups([
                NavigationGroup::make('Website')
                    ->collapsible(false),
            ])
            ->navigationItems([
                NavigationItem::make('User Manual')
                    ->group('Website')
                    ->icon(Heroicon::OutlinedBookOpen)
                    ->sort(940)
                    ->url(fn (): string => route('manual'), true),
            ])
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                fn (): HtmlString => new HtmlString(<<<'HTML'
                    <style>
                        .twyxtco-content-block-builder-field {
                            position: relative;
                        }

                        .twyxtco-content-block-builder-field > .fi-fo-field-label-col {
                            padding-inline-end: 18rem;
                        }

                        .twyxtco-content-block-builder-field .fi-fo-field-label-content {
                            color: var(--gray-950);
                            font-size: 1.125rem;
                            font-weight: 750;
                            line-height: 1.25;
                        }

                        .dark .twyxtco-content-block-builder-field .fi-fo-field-label-content {
                            color: white;
                        }

                        .twyxtco-content-block-builder-field .fi-fo-builder > .fi-fo-builder-actions {
                            position: absolute;
                            top: 0;
                            inset-inline-end: 0;
                            align-items: center;
                            gap: 0;
                        }

                        .twyxtco-content-block-builder-field .fi-fo-builder > .fi-fo-builder-actions > span + span {
                            margin-inline-start: 1.25rem;
                            padding-inline-start: 1.25rem;
                            border-inline-start: 1px solid var(--gray-300);
                        }

                        .dark .twyxtco-content-block-builder-field .fi-fo-builder > .fi-fo-builder-actions > span + span {
                            border-inline-start-color: var(--gray-700);
                        }

                        .twyxtco-admin-icon-action.fi-icon-btn {
                            width: 3rem;
                            height: 3.75rem;
                            margin: -0.5rem;
                            border-radius: 0.75rem;
                        }

                        .twyxtco-admin-icon-action.fi-icon-btn > .fi-icon {
                            width: 1.875rem;
                            height: 1.875rem;
                        }

                        .twyxtco-ai-rewrite-modal .fi-modal-heading {
                            font-size: 1.75rem;
                            line-height: 1.2;
                        }

                        .twyxtco-ai-rewrite-modal .fi-modal-window {
                            max-height: calc(100dvh - 2rem);
                        }

                        .twyxtco-ai-rewrite-modal .fi-modal-content {
                            overflow-y: auto;
                        }

                        .twyxtco-file-extraction-modal.fi-modal-window,
                        .twyxtco-file-extraction-modal .fi-modal-window {
                            display: flex;
                            flex-direction: column;
                            max-height: calc(100dvh - 2rem);
                            min-height: 0;
                            overflow: hidden;
                        }

                        .twyxtco-file-extraction-modal form,
                        .twyxtco-file-extraction-modal .fi-modal-content-ctn {
                            display: flex;
                            flex: 1 1 auto;
                            flex-direction: column;
                            min-height: 0;
                        }

                        .twyxtco-file-extraction-modal .fi-modal-content {
                            flex: 1 1 auto;
                            min-height: 0;
                            overflow-y: auto;
                            overscroll-behavior: contain;
                            padding-bottom: 2rem;
                        }

                        .twyxtco-file-extraction-modal .fi-modal-footer {
                            flex: 0 0 auto;
                        }

                        .twyxtco-file-extraction-prompt-field textarea {
                            max-height: 14rem;
                            overflow-y: auto;
                            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
                            font-size: 0.8125rem;
                            line-height: 1.45;
                        }

                        .twyxtco-file-extraction-result-editor {
                            height: clamp(18rem, calc(100dvh - 34rem), 44rem);
                            max-height: calc(100dvh - 20px);
                            min-height: 18rem;
                            overflow-y: auto !important;
                            overscroll-behavior: contain;
                        }

                        .twyxtco-file-extraction-result-editor .fi-fo-rich-editor-content {
                            height: 100%;
                            max-height: inherit;
                            min-height: 100%;
                            overflow-y: auto;
                            overscroll-behavior: contain;
                        }

                        .twyxtco-ai-page-review-modal.fi-modal-window,
                        .twyxtco-ai-page-review-modal .fi-modal-window {
                            display: flex;
                            flex-direction: column;
                            max-height: 100dvh;
                            min-height: 0;
                            overflow: hidden;
                        }

                        .twyxtco-ai-page-review-modal form,
                        .twyxtco-ai-page-review-modal .fi-modal-content-ctn {
                            display: flex;
                            flex: 1 1 auto;
                            flex-direction: column;
                            min-height: 0;
                        }

                        .twyxtco-ai-page-review-modal .fi-modal-content {
                            flex: 1 1 auto;
                            min-height: 0;
                            overflow-y: auto;
                            overscroll-behavior: contain;
                            padding-bottom: 2rem;
                        }

                        .twyxtco-ai-page-review-modal .fi-modal-footer {
                            flex: 0 0 auto;
                        }

                        .twyxtco-ai-page-review-actions {
                            position: relative;
                            display: flex !important;
                            flex-direction: row !important;
                            flex-wrap: nowrap;
                            align-items: center;
                            justify-content: center;
                            gap: 0.75rem;
                            width: 100%;
                            padding-block: 0.125rem 0.375rem;
                        }

                        .twyxtco-ai-page-review-visual {
                            display: grid;
                            gap: 0.75rem;
                            border: 1px solid var(--gray-300);
                            border-radius: 0.5rem;
                            padding: 0.875rem;
                            background: var(--gray-50);
                        }

                        .dark .twyxtco-ai-page-review-visual {
                            border-color: var(--gray-700);
                            background: var(--gray-900);
                        }

                        .twyxtco-ai-page-review-visual-header {
                            display: flex;
                            flex-wrap: wrap;
                            align-items: center;
                            justify-content: space-between;
                            gap: 0.75rem;
                        }

                        .twyxtco-ai-page-review-visual-title {
                            color: var(--gray-950);
                            font-size: 0.95rem;
                            font-weight: 700;
                        }

                        .dark .twyxtco-ai-page-review-visual-title {
                            color: white;
                        }

                        .twyxtco-ai-page-review-visual-link {
                            color: var(--primary-600);
                            font-size: 0.875rem;
                            font-weight: 700;
                            text-decoration: underline;
                            text-underline-offset: 0.1875rem;
                        }

                        .dark .twyxtco-ai-page-review-visual-link {
                            color: var(--primary-400);
                        }

                        .twyxtco-ai-page-review-visual-preview {
                            display: block;
                            border: 1px solid var(--gray-300);
                            border-radius: 0.375rem;
                            background: white;
                        }

                        .dark .twyxtco-ai-page-review-visual-preview {
                            border-color: var(--gray-700);
                            background: black;
                        }

                        .twyxtco-ai-page-review-visual-preview img {
                            display: block;
                            width: 100%;
                            height: auto;
                        }

                        .twyxtco-ai-page-review-result-field textarea {
                            max-height: var(--twyxtco-ai-page-review-visual-height, none);
                            overflow-y: auto;
                        }

                        .twyxtco-ai-page-review-action-btn.fi-icon-btn {
                            display: inline-flex !important;
                            width: 3.75rem !important;
                            height: 3.75rem !important;
                            align-items: center !important;
                            justify-content: center !important;
                            border-radius: 0.5rem !important;
                        }

                        .twyxtco-ai-page-review-action-btn.fi-icon-btn > .fi-icon {
                            width: 2.5rem !important;
                            height: 2.5rem !important;
                        }

                        .twyxtco-ai-page-review-processing {
                            position: fixed;
                            z-index: 60;
                            inset: 0;
                            display: none;
                            align-items: center;
                            justify-content: center;
                            gap: 1rem;
                            padding: 1.25rem;
                            background: color-mix(in oklab, black 55%, transparent);
                            backdrop-filter: blur(2px);
                        }

                        .twyxtco-ai-page-review-processing-spinner {
                            width: 2rem;
                            height: 2rem;
                            color: var(--primary-400);
                        }

                        .twyxtco-ai-page-review-processing-title {
                            color: white;
                            font-size: 1rem;
                            font-weight: 700;
                        }

                        .twyxtco-ai-page-review-processing-message {
                            color: var(--gray-300);
                            font-size: 0.875rem;
                        }

                        .twyxtco-ai-rewrite-prompt-field .fi-fo-field-label-content,
                        .twyxtco-ai-rewrite-suggestion-field .fi-fo-field-label-content {
                            font-size: 1.125rem;
                            line-height: 1.35;
                        }

                        .twyxtco-ai-rewrite-prompt-field .fi-sc-text {
                            font-size: 0.9375rem;
                            line-height: 1.45;
                        }

                        .twyxtco-ai-rewrite-actions {
                            position: relative;
                            display: flex !important;
                            flex-direction: row !important;
                            flex-wrap: nowrap;
                            align-items: center;
                            justify-content: center;
                            gap: 0.75rem;
                            width: 100%;
                            padding-block: 0.125rem 0.375rem;
                        }

                        .twyxtco-ai-rewrite-action-btn.fi-icon-btn {
                            display: inline-flex !important;
                            width: 3.75rem !important;
                            height: 3.75rem !important;
                            align-items: center !important;
                            justify-content: center !important;
                            border-radius: 0.5rem !important;
                        }

                        .twyxtco-ai-rewrite-action-btn.fi-icon-btn > .fi-icon {
                            width: 2.5rem !important;
                            height: 2.5rem !important;
                        }

                        .twyxtco-ai-rewrite-comparison-editor {
                            height: clamp(14rem, calc(100dvh - 33rem), 42rem);
                            max-height: calc(100dvh - 20px);
                            min-height: 14rem;
                            overflow-y: auto !important;
                            overscroll-behavior: contain;
                        }

                        .twyxtco-ai-rewrite-comparison-editor .fi-fo-rich-editor-content {
                            height: 100%;
                            max-height: inherit;
                            min-height: 100%;
                            overflow-y: auto;
                            overscroll-behavior: contain;
                        }

                        .twyxtco-ai-rewrite-suggestion-field {
                            margin-bottom: 50px;
                        }

                        .twyxtco-ai-rewrite-processing {
                            position: fixed;
                            top: 50%;
                            left: 50%;
                            z-index: 60;
                            display: none;
                            width: min(24rem, calc(100vw - 2rem));
                            align-items: center;
                            gap: 0.875rem;
                            padding: 1rem;
                            border: 1px solid rgb(245 158 11 / 0.35);
                            border-radius: 0.75rem;
                            background: white;
                            color: var(--gray-950);
                            box-shadow: 0 20px 45px rgb(0 0 0 / 0.24);
                            transform: translate(-50%, -50%);
                        }

                        .dark .twyxtco-ai-rewrite-processing {
                            border-color: rgb(245 158 11 / 0.45);
                            background: var(--gray-900);
                            color: white;
                        }

                        .twyxtco-ai-rewrite-processing-spinner {
                            width: 2rem;
                            height: 2rem;
                            flex-shrink: 0;
                            color: rgb(217 119 6);
                        }

                        .dark .twyxtco-ai-rewrite-processing-spinner {
                            color: rgb(251 191 36);
                        }

                        .twyxtco-ai-rewrite-processing-title {
                            font-size: 0.9375rem;
                            font-weight: 700;
                            line-height: 1.35;
                        }

                        .twyxtco-ai-rewrite-processing-message {
                            margin-top: 0.125rem;
                            color: var(--gray-600);
                            font-size: 0.8125rem;
                            line-height: 1.4;
                        }

                        .dark .twyxtco-ai-rewrite-processing-message {
                            color: var(--gray-300);
                        }

                        .twyxtco-user-permission-list .fi-fo-checkbox-list-option-ctn {
                            padding-inline-start: 1.25rem;
                        }

                        .twyxtco-user-permission-list .fi-fo-checkbox-list-actions {
                            padding-inline-start: 1.25rem;
                        }

                        .twyxtco-user-permission-list .fi-fo-checkbox-list-option {
                            gap: 0.625rem;
                        }

                        .fi-sidebar-item-btn.twyxtco-sidebar-help-ready {
                            gap: 0.75rem;
                        }

                        .fi-sidebar-open .fi-sidebar-item.twyxtco-sidebar-indent-35 > .fi-sidebar-item-btn {
                            margin-inline-start: 35px;
                            width: calc(100% - 35px);
                            margin-top: -4px;
                        }

                        .fi-sidebar-open .fi-sidebar-item.twyxtco-sidebar-site-tools-divider {
                            margin-top: 0.75rem;
                            padding-top: 0.75rem;
                            border-top: 1px solid rgb(209 213 219);
                        }

                        .dark .fi-sidebar-open .fi-sidebar-item.twyxtco-sidebar-site-tools-divider {
                            border-top-color: rgb(75 85 99);
                        }

                        .twyxtco-sidebar-help {
                            display: inline-flex;
                            align-items: center;
                            justify-content: center;
                            width: 1.375rem;
                            height: 1.375rem;
                            margin-inline-start: auto;
                            border: 1px solid rgb(156 163 175 / 0.7);
                            border-radius: 0.3125rem;
                            background: rgb(249 250 251);
                            color: rgb(107 114 128);
                            font-size: 0.8125rem;
                            font-weight: 700;
                            line-height: 1;
                            cursor: pointer;
                            flex-shrink: 0;
                        }

                        .dark .twyxtco-sidebar-help {
                            border-color: rgb(107 114 128 / 0.75);
                            background: rgb(31 41 55 / 0.72);
                            color: rgb(209 213 219);
                        }

                        .twyxtco-sidebar-help:hover,
                        .twyxtco-sidebar-help:focus {
                            border-color: rgb(34 197 94);
                            background: rgb(34 197 94 / 0.14);
                            color: rgb(22 163 74);
                            outline: none;
                        }

                        .dark .twyxtco-sidebar-help:hover,
                        .dark .twyxtco-sidebar-help:focus {
                            border-color: rgb(74 222 128);
                            background: rgb(34 197 94 / 0.18);
                            color: rgb(134 239 172);
                        }

                        .fi-sidebar:not(.fi-sidebar-open) .twyxtco-sidebar-help {
                            display: none;
                        }

                        .twyxtco-admin-nav-help-tooltip {
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

                        .twyxtco-dashboard-global-controls-host {
                            position: relative;
                        }

                        .twyxtco-dashboard-global-controls {
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

                        .twyxtco-dashboard-global-control {
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

                        .dark .twyxtco-dashboard-global-control {
                            border-color: rgb(245 158 11 / 0.5);
                            background: rgb(17 24 39);
                            color: rgb(251 191 36);
                        }

                        .twyxtco-dashboard-global-control:hover,
                        .twyxtco-dashboard-global-control:focus {
                            border-color: rgb(245 158 11);
                            background: rgb(255 251 235);
                            color: rgb(180 83 9);
                            outline: none;
                        }

                        .dark .twyxtco-dashboard-global-control:hover,
                        .dark .twyxtco-dashboard-global-control:focus {
                            border-color: rgb(245 158 11);
                            background: rgb(69 26 3 / 0.3);
                            color: rgb(251 191 36);
                        }

                        .twyxtco-cms-dashboard-widgets > .fi-sc {
                            display: flex !important;
                            align-items: flex-start;
                            gap: 1.5rem;
                        }

                        .twyxtco-dashboard-widget-column {
                            display: grid;
                            flex: 1 1 0;
                            gap: 1.5rem;
                            min-width: 0;
                        }

                        .twyxtco-cms-dashboard-widgets > .fi-sc > .fi-wi-widget,
                        .twyxtco-dashboard-widget-column > .fi-wi-widget {
                            display: block;
                            width: 100%;
                            min-width: 0;
                        }

                        .twyxtco-dashboard-widget {
                            transition: opacity 120ms ease, outline-color 120ms ease, transform 120ms ease;
                        }

                        .twyxtco-dashboard-widget .fi-section {
                            position: relative;
                            overflow: hidden;
                            border-color: rgb(229 231 235);
                            background: linear-gradient(180deg, rgb(255 255 255), rgb(249 250 251));
                            box-shadow: 0 12px 30px rgb(15 23 42 / 0.06);
                        }

                        .dark .twyxtco-dashboard-widget .fi-section {
                            border-color: rgb(31 41 55);
                            background: linear-gradient(180deg, rgb(24 24 27), rgb(17 24 39));
                            box-shadow: 0 18px 36px rgb(0 0 0 / 0.24);
                        }

                        .twyxtco-dashboard-widget-shell {
                            display: grid;
                            gap: 0.875rem;
                        }

                        .twyxtco-dashboard-widget-controls {
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

                        .twyxtco-dashboard-widget-controls > * {
                            pointer-events: auto;
                        }

                        .twyxtco-dashboard-widget-header {
                            min-width: 0;
                            padding-top: 0;
                            padding-inline-end: 5.5rem;
                            padding-bottom: 0.875rem;
                            border-bottom: 1px solid rgb(229 231 235);
                        }

                        .dark .twyxtco-dashboard-widget-header {
                            border-bottom-color: rgb(55 65 81);
                        }

                        .twyxtco-dashboard-widget-title {
                            margin: 0;
                            color: rgb(17 24 39);
                            font-size: 1.05rem;
                            font-weight: 800;
                            line-height: 1.2;
                        }

                        .dark .twyxtco-dashboard-widget-title {
                            color: white;
                        }

                        .twyxtco-dashboard-widget-description {
                            margin-top: 0.35rem;
                            color: rgb(75 85 99);
                            font-size: 0.8125rem;
                            font-weight: 500;
                            line-height: 1.45;
                        }

                        .dark .twyxtco-dashboard-widget-description {
                            color: rgb(156 163 175);
                        }

                        .twyxtco-dashboard-widget-action,
                        .twyxtco-dashboard-widget-count,
                        .twyxtco-dashboard-widget-drag-handle,
                        .twyxtco-dashboard-widget-collapse {
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

                        .twyxtco-dashboard-widget-action {
                            height: 2rem;
                            padding-inline: 0.625rem;
                        }

                        .twyxtco-dashboard-widget-count {
                            width: 2rem;
                            height: 2rem;
                            padding: 0;
                            border-color: rgb(14 116 144 / 0.42);
                            background: rgb(236 254 255);
                            color: rgb(21 94 117);
                            cursor: default;
                        }

                        .twyxtco-dashboard-widget-count--danger {
                            border-color: rgb(220 38 38 / 0.55);
                            background: rgb(254 226 226);
                            color: rgb(153 27 27);
                        }

                        .twyxtco-dashboard-widget-count--warning {
                            border-color: rgb(217 119 6 / 0.55);
                            background: rgb(254 243 199);
                            color: rgb(146 64 14);
                        }

                        .twyxtco-dashboard-widget-count--success {
                            border-color: rgb(22 163 74 / 0.55);
                            background: rgb(220 252 231);
                            color: rgb(21 128 61);
                        }

                        .twyxtco-dashboard-widget-drag-handle,
                        .twyxtco-dashboard-widget-collapse {
                            width: 2rem;
                            height: 2rem;
                            padding: 0;
                        }

                        .twyxtco-dashboard-widget-control-icon {
                            width: 1rem;
                            height: 1rem;
                            flex-shrink: 0;
                            pointer-events: none;
                        }

                        .twyxtco-dashboard-widget-collapse-icon-collapsed {
                            display: none;
                        }

                        .twyxtco-dashboard-widget.twyxtco-dashboard-widget-collapsed .twyxtco-dashboard-widget-collapse-icon-expanded {
                            display: none;
                        }

                        .twyxtco-dashboard-widget.twyxtco-dashboard-widget-collapsed .twyxtco-dashboard-widget-collapse-icon-collapsed {
                            display: block;
                        }

                        .dark .twyxtco-dashboard-widget-action,
                        .dark .twyxtco-dashboard-widget-drag-handle,
                        .dark .twyxtco-dashboard-widget-collapse {
                            border-color: rgb(75 85 99);
                            background: rgb(17 24 39);
                            color: rgb(229 231 235);
                        }

                        .dark .twyxtco-dashboard-widget-count {
                            border-color: rgb(34 211 238 / 0.34);
                            background: rgb(8 47 73 / 0.65);
                            color: rgb(165 243 252);
                        }

                        .dark .twyxtco-dashboard-widget-count--danger {
                            border-color: rgb(248 113 113 / 0.48);
                            background: rgb(127 29 29 / 0.56);
                            color: rgb(254 202 202);
                        }

                        .dark .twyxtco-dashboard-widget-count--warning {
                            border-color: rgb(251 191 36 / 0.48);
                            background: rgb(120 53 15 / 0.5);
                            color: rgb(253 230 138);
                        }

                        .dark .twyxtco-dashboard-widget-count--success {
                            border-color: rgb(74 222 128 / 0.45);
                            background: rgb(20 83 45 / 0.55);
                            color: rgb(187 247 208);
                        }

                        .twyxtco-dashboard-widget-action {
                            border-color: rgb(245 158 11 / 0.45);
                            color: rgb(180 83 9);
                        }

                        .dark .twyxtco-dashboard-widget-action {
                            border-color: rgb(245 158 11 / 0.5);
                            color: rgb(251 191 36);
                        }

                        .twyxtco-dashboard-widget-drag-handle {
                            cursor: grab;
                            touch-action: none;
                        }

                        .twyxtco-dashboard-widget-drag-handle:active {
                            cursor: grabbing;
                        }

                        .twyxtco-dashboard-widget-action:hover,
                        .twyxtco-dashboard-widget-action:focus,
                        .twyxtco-dashboard-widget-drag-handle:hover,
                        .twyxtco-dashboard-widget-drag-handle:focus,
                        .twyxtco-dashboard-widget-collapse:hover,
                        .twyxtco-dashboard-widget-collapse:focus {
                            border-color: rgb(245 158 11);
                            background: rgb(255 251 235);
                            color: rgb(180 83 9);
                            outline: none;
                        }

                        .dark .twyxtco-dashboard-widget-action:hover,
                        .dark .twyxtco-dashboard-widget-action:focus,
                        .dark .twyxtco-dashboard-widget-drag-handle:hover,
                        .dark .twyxtco-dashboard-widget-drag-handle:focus,
                        .dark .twyxtco-dashboard-widget-collapse:hover,
                        .dark .twyxtco-dashboard-widget-collapse:focus {
                            border-color: rgb(245 158 11);
                            background: rgb(69 26 3 / 0.3);
                            color: rgb(251 191 36);
                        }

                        .twyxtco-dashboard-widget-body {
                            display: grid;
                            gap: 0.625rem;
                            margin-top: 1rem;
                        }

                        .twyxtco-dashboard-widget-row {
                            display: flex;
                            align-items: center;
                            gap: 0.875rem;
                            min-width: 0;
                            border: 1px solid rgb(229 231 235);
                            border-radius: 0.5rem;
                            background: rgb(255 255 255 / 0.78);
                            padding: 0.75rem;
                        }

                        .twyxtco-dashboard-widget-row > .min-w-0.flex-1 {
                            flex: 1 1 auto;
                            min-width: 0;
                        }

                        .twyxtco-dashboard-widget-row-status {
                            margin-inline-start: auto;
                            flex-shrink: 0;
                        }

                        .dark .twyxtco-dashboard-widget-row {
                            border-color: rgb(55 65 81);
                            background: rgb(3 7 18 / 0.38);
                        }

                        .twyxtco-dashboard-widget-row-image {
                            width: 4.75rem;
                            height: 3.25rem;
                            flex-shrink: 0;
                            border-radius: 0.5rem;
                            object-fit: cover;
                            box-shadow: inset 0 0 0 1px rgb(15 23 42 / 0.1);
                        }

                        .twyxtco-dashboard-widget-row-image-link {
                            display: block;
                            flex-shrink: 0;
                            border-radius: 0.5rem;
                            outline: none;
                        }

                        .twyxtco-dashboard-widget-row-image-link:hover .twyxtco-dashboard-widget-row-image,
                        .twyxtco-dashboard-widget-row-image-link:focus .twyxtco-dashboard-widget-row-image {
                            box-shadow: 0 0 0 2px rgb(245 158 11 / 0.75);
                        }

                        .twyxtco-dashboard-widget-type {
                            display: inline-flex;
                            align-items: center;
                            max-width: 100%;
                            color: rgb(146 64 14);
                            font-size: 0.825rem;
                            font-weight: 800;
                            line-height: 1;
                            overflow-wrap: anywhere;
                        }

                        .dark .twyxtco-dashboard-widget-type {
                            color: rgb(251 191 36);
                        }

                        .twyxtco-dashboard-widget-row-title {
                            display: block;
                            color: rgb(17 24 39);
                            font-size: 0.875rem;
                            font-weight: 750;
                            line-height: 1.35;
                            text-decoration: none;
                            overflow-wrap: anywhere;
                            white-space: normal;
                        }

                        .dark .twyxtco-dashboard-widget-row-title {
                            color: white;
                        }

                        .twyxtco-dashboard-widget-row-title:hover,
                        .twyxtco-dashboard-widget-row-title:focus {
                            color: rgb(217 119 6);
                            outline: none;
                        }

                        .dark .twyxtco-dashboard-widget-row-title:hover,
                        .dark .twyxtco-dashboard-widget-row-title:focus {
                            color: rgb(251 191 36);
                        }

                        .twyxtco-dashboard-widget-row-meta {
                            margin-top: 0.25rem;
                            color: rgb(107 114 128);
                            font-size: 0.75rem;
                            font-weight: 500;
                            line-height: 1.35;
                            overflow-wrap: anywhere;
                            white-space: normal;
                        }

                        .dark .twyxtco-dashboard-widget-row-meta {
                            color: rgb(156 163 175);
                        }

                        .twyxtco-dashboard-widget-empty {
                            border: 1px dashed rgb(209 213 219);
                            border-radius: 0.5rem;
                            color: rgb(107 114 128);
                            font-size: 0.875rem;
                            font-weight: 600;
                            line-height: 1.45;
                            padding: 0.875rem;
                        }

                        .dark .twyxtco-dashboard-widget-empty {
                            border-color: rgb(75 85 99);
                            color: rgb(156 163 175);
                        }

                        .twyxtco-dashboard-widget.twyxtco-dashboard-widget-dragging {
                            opacity: 0.92;
                            pointer-events: none;
                            transform: rotate(0.35deg);
                        }

                        .twyxtco-dashboard-widget-placeholder {
                            display: block;
                            width: 100%;
                            border: 2px dashed rgb(245 158 11);
                            border-radius: 0.75rem;
                            background: rgb(245 158 11 / 0.09);
                        }

                        .twyxtco-dashboard-widget.twyxtco-dashboard-widget-collapsed [data-twyxtco-dashboard-widget-body],
                        .twyxtco-dashboard-widget.twyxtco-dashboard-widget-collapsed [data-twyxtco-dashboard-widget-description] {
                            display: none;
                        }

                        @media (max-width: 640px) {
                            .twyxtco-dashboard-global-controls {
                                position: static;
                                justify-content: flex-start;
                                margin-bottom: 1rem;
                                transform: none;
                            }

                            .twyxtco-content-block-builder-field > .fi-fo-field-label-col {
                                padding-inline-end: 0;
                            }

                            .twyxtco-content-block-builder-field .fi-fo-builder > .fi-fo-builder-actions {
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
                function (): HtmlString {
                    $descriptions = Js::from(AdminNavigationHelp::descriptions());
                    $manualUrls = [];

                    foreach (AdminNavigationHelp::manualAnchors() as $label => $anchor) {
                        $manualUrls[$label] = route('manual').'#'.$anchor;
                    }

                    $manualUrls = Js::from($manualUrls);

                    return new HtmlString(<<<HTML
                    <script>
                        (() => {
                            const descriptions = {$descriptions};
                            const manualUrls = {$manualUrls};
                            let tooltip = null;

                            const normalizeLabel = (value) => value.replace(/\\s+/g, ' ').trim();

                            const ensureTooltip = () => {
                                if (tooltip) {
                                    return tooltip;
                                }

                                tooltip = document.createElement('div');
                                tooltip.className = 'twyxtco-admin-nav-help-tooltip';
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
                                const content = trigger.dataset.twyxtcoHelp;

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
                                    if (link.querySelector('.twyxtco-sidebar-help')) {
                                        return;
                                    }

                                    const label = normalizeLabel(link.querySelector('.fi-sidebar-item-label')?.textContent ?? '');
                                    const description = descriptions[label];
                                    const manualUrl = manualUrls[label];

                                    if (! description) {
                                        return;
                                    }

                                    const icon = document.createElement('span');
                                    icon.className = 'twyxtco-sidebar-help';
                                    icon.textContent = 'i';
                                    icon.setAttribute('role', 'button');
                                    icon.setAttribute('tabindex', '0');
                                    icon.setAttribute('aria-label', 'About ' + label + ': ' + description + ' Open the related user manual section.');
                                    icon.dataset.twyxtcoHelp = description;
                                    if (manualUrl) {
                                        icon.dataset.twyxtcoManualUrl = manualUrl;
                                    }

                                    const openManualSection = () => {
                                        if (! icon.dataset.twyxtcoManualUrl) {
                                            showTooltip(icon);

                                            return;
                                        }

                                        hideTooltip();
                                        window.open(icon.dataset.twyxtcoManualUrl, '_blank', 'noopener');
                                    };

                                    icon.addEventListener('click', (event) => {
                                        event.preventDefault();
                                        event.stopPropagation();
                                        openManualSection();
                                    });

                                    icon.addEventListener('keydown', (event) => {
                                        if ((event.key !== 'Enter') && (event.key !== ' ')) {
                                            return;
                                        }

                                        event.preventDefault();
                                        event.stopPropagation();
                                        openManualSection();
                                    });

                                    icon.addEventListener('mouseenter', () => showTooltip(icon));
                                    icon.addEventListener('mouseleave', hideTooltip);
                                    icon.addEventListener('focus', () => showTooltip(icon));
                                    icon.addEventListener('blur', hideTooltip);

                                    link.classList.add('twyxtco-sidebar-help-ready');
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
                        document.addEventListener('twyxtco-focus-first-form-field', () => {
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
                            const storageKey = 'twyxtco.admin.dashboard.widgets.v1';

                            const dashboardContainer = () => document.querySelector('.twyxtco-cms-dashboard-widgets > .fi-sc');

                            const dashboardWidgets = () => {
                                const container = dashboardContainer();

                                if (! container) {
                                    return [];
                                }

                                return Array.from(container.querySelectorAll('.twyxtco-dashboard-widget[data-twyxtco-dashboard-widget]'));
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

                                return Array.from(container.querySelectorAll(':scope > .twyxtco-dashboard-widget-column'));
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
                                    const key = widget.dataset.twyxtcoDashboardWidget;

                                    state.collapsed[key] = collapsed;
                                    applyCollapsedState(widget, collapsed);
                                });

                                writeState(state);
                            };

                            const ensureDashboardGlobalControls = () => {
                                const dashboard = document.querySelector('.twyxtco-cms-dashboard');
                                const host = dashboard?.querySelector('.fi-header') ?? dashboard;

                                if (! host || host.querySelector('.twyxtco-dashboard-global-controls')) {
                                    return;
                                }

                                host.classList.add('twyxtco-dashboard-global-controls-host');

                                const controls = document.createElement('div');
                                controls.className = 'twyxtco-dashboard-global-controls';

                                const expandButton = document.createElement('button');
                                expandButton.type = 'button';
                                expandButton.className = 'twyxtco-dashboard-global-control';
                                expandButton.textContent = 'Expand All';
                                expandButton.setAttribute('aria-label', 'Expand all dashboard boxes');
                                expandButton.addEventListener('click', () => setAllCollapsed(false));

                                const collapseButton = document.createElement('button');
                                collapseButton.type = 'button';
                                collapseButton.className = 'twyxtco-dashboard-global-control';
                                collapseButton.textContent = 'Collapse All';
                                collapseButton.setAttribute('aria-label', 'Collapse all dashboard boxes');
                                collapseButton.addEventListener('click', () => setAllCollapsed(true));

                                controls.append(expandButton, collapseButton);
                                host.append(controls);
                            };

                            const applyCollapsedState = (widget, collapsed) => {
                                const heading = widgetHeading(widget);
                                const button = widget.querySelector('[data-twyxtco-dashboard-widget-collapse]');

                                widget.classList.toggle('twyxtco-dashboard-widget-collapsed', collapsed);

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
                                const byKey = new Map(widgets.map((widget) => [widget.dataset.twyxtcoDashboardWidget, widget]));
                                const ordered = [];

                                state.order.forEach((key) => {
                                    if (byKey.has(key)) {
                                        ordered.push(byKey.get(key));
                                        byKey.delete(key);
                                    }
                                });

                                const columns = Array.from({ length: dashboardColumnCount() }, () => {
                                    const column = document.createElement('div');
                                    column.className = 'twyxtco-dashboard-widget-column';

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
                                    ? columns.flatMap((column) => Array.from(column.querySelectorAll(':scope > .twyxtco-dashboard-widget[data-twyxtco-dashboard-widget]')))
                                    : dashboardWidgets();

                                state.order = widgets.map((widget) => widget.dataset.twyxtcoDashboardWidget);
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
                                widget.classList.remove('twyxtco-dashboard-widget-dragging');
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
                                placeholder.className = 'twyxtco-dashboard-widget-placeholder';
                                placeholder.style.height = rect.height + 'px';
                                widget.after(placeholder);

                                const offsetX = event.clientX - rect.left;
                                const offsetY = event.clientY - rect.top;

                                widget.classList.add('twyxtco-dashboard-widget-dragging');
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

                                    const targets = Array.from(column.querySelectorAll(':scope > .twyxtco-dashboard-widget[data-twyxtco-dashboard-widget]'))
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
                                    const key = widget.dataset.twyxtcoDashboardWidget;
                                    const heading = widgetHeading(widget);
                                    const collapseButton = widget.querySelector('[data-twyxtco-dashboard-widget-collapse]');
                                    const dragHandle = widget.querySelector('[data-twyxtco-dashboard-widget-drag-handle]');

                                    applyCollapsedState(widget, Boolean(state.collapsed[key]));

                                    if (widget.dataset.twyxtcoDashboardReady === 'true') {
                                        return;
                                    }

                                    widget.dataset.twyxtcoDashboardReady = 'true';

                                    if (dragHandle) {
                                        dragHandle.addEventListener('pointerdown', (event) => startWidgetDrag(event, widget));
                                    }

                                    if (collapseButton) {
                                        collapseButton.addEventListener('click', () => {
                                            const nextState = readState();
                                            const shouldCollapse = ! widget.classList.contains('twyxtco-dashboard-widget-collapsed');

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

    private function brandName(): string
    {
        if (! Schema::hasTable('site_settings')) {
            return 'TwyxtCo Church Dashboard';
        }

        return (SiteSetting::query()->value('church_name') ?: 'TwyxtCo Church').' Dashboard';
    }
}
