<x-filament-panels::page>
    @php
        $metrics = $this->summaryMetrics();
        $trend = $this->dailyTrend();
        $topPages = $this->topPages();
        $topReferrers = $this->topReferrers();
        $devices = $this->deviceBreakdown();
        $browsers = $this->browserBreakdown();
        $platforms = $this->platformBreakdown();
        $countries = $this->countryBreakdown();
        $regions = $this->regionBreakdown();
        $cities = $this->cityBreakdown();
        $recentViews = $this->recentViews();
        $maxTrendViews = $this->maxValue($trend, 'views');
        $maxPageViews = $this->maxValue($topPages, 'views');
        $maxReferrerViews = $this->maxValue($topReferrers, 'views');
    @endphp

    <style>
        .twyxtco-analytics {
            --twyxtco-panel-border: rgb(229 231 235);
            --twyxtco-panel-bg: white;
            --twyxtco-panel-soft: rgb(249 250 251);
            --twyxtco-text-muted: rgb(107 114 128);
            --twyxtco-accent: rgb(202 138 4);
            --twyxtco-accent-soft: rgb(254 249 195);
            --twyxtco-bar: rgb(14 165 233);
            --twyxtco-bar-soft: rgb(224 242 254);
        }

        .dark .twyxtco-analytics {
            --twyxtco-panel-border: rgb(55 65 81);
            --twyxtco-panel-bg: rgb(17 24 39);
            --twyxtco-panel-soft: rgb(3 7 18);
            --twyxtco-text-muted: rgb(156 163 175);
            --twyxtco-accent: rgb(250 204 21);
            --twyxtco-accent-soft: rgba(250, 204, 21, 0.12);
            --twyxtco-bar: rgb(56 189 248);
            --twyxtco-bar-soft: rgba(56, 189, 248, 0.13);
        }

        .twyxtco-analytics-card {
            border: 1px solid var(--twyxtco-panel-border);
            border-radius: 0.75rem;
            background: var(--twyxtco-panel-bg);
            padding: 1rem;
        }

        .twyxtco-analytics-controls {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 0.75rem;
        }

        .twyxtco-analytics-control label,
        .twyxtco-analytics-section-label {
            display: block;
            margin-bottom: 0.35rem;
            color: var(--twyxtco-text-muted);
            font-size: 0.75rem;
            font-weight: 700;
        }

        .twyxtco-analytics-control select {
            width: 100%;
            border: 1px solid var(--twyxtco-panel-border);
            border-radius: 0.5rem;
            background: var(--twyxtco-panel-bg);
            padding: 0.5rem 0.75rem;
            color: inherit;
            font-size: 0.875rem;
        }

        .twyxtco-analytics-metrics {
            display: grid;
            grid-template-columns: repeat(6, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .twyxtco-analytics-metric {
            min-width: 0;
            border: 1px solid var(--twyxtco-panel-border);
            border-radius: 0.75rem;
            background: var(--twyxtco-panel-bg);
            padding: 0.875rem;
        }

        .twyxtco-analytics-metric__value {
            color: rgb(17 24 39);
            font-size: 1.5rem;
            font-weight: 800;
            line-height: 1.1;
        }

        .dark .twyxtco-analytics-metric__value {
            color: white;
        }

        .twyxtco-analytics-muted {
            color: var(--twyxtco-text-muted);
        }

        .twyxtco-analytics-trend {
            display: grid;
            grid-auto-flow: column;
            grid-auto-columns: minmax(0, 1fr);
            gap: 0.25rem;
            align-items: end;
            min-height: 9rem;
            padding-top: 0.5rem;
        }

        .twyxtco-analytics-trend__bar {
            display: flex;
            min-width: 0;
            height: 8rem;
            align-items: end;
            justify-content: center;
            border-radius: 0.5rem;
            background: var(--twyxtco-bar-soft);
        }

        .twyxtco-analytics-trend__fill {
            width: 100%;
            min-height: 0.25rem;
            border-radius: 0.5rem;
            background: var(--twyxtco-bar);
        }

        .twyxtco-analytics-table {
            display: grid;
            gap: 0.5rem;
        }

        .twyxtco-analytics-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) 5rem 5rem 7rem;
            gap: 0.75rem;
            align-items: center;
            border: 1px solid var(--twyxtco-panel-border);
            border-radius: 0.65rem;
            background: var(--twyxtco-panel-soft);
            padding: 0.75rem;
        }

        .twyxtco-analytics-row--three {
            grid-template-columns: minmax(0, 1fr) 5rem 7rem;
        }

        .twyxtco-analytics-title {
            overflow-wrap: anywhere;
            color: rgb(17 24 39);
            font-weight: 800;
        }

        .dark .twyxtco-analytics-title {
            color: white;
        }

        .twyxtco-analytics-kicker {
            color: var(--twyxtco-accent);
            font-size: 0.8125rem;
            font-weight: 800;
            overflow-wrap: anywhere;
        }

        .twyxtco-analytics-stat {
            text-align: right;
        }

        .twyxtco-analytics-stat strong {
            display: block;
            color: rgb(17 24 39);
            font-size: 0.95rem;
        }

        .dark .twyxtco-analytics-stat strong {
            color: white;
        }

        .twyxtco-analytics-mini-bar {
            overflow: hidden;
            height: 0.5rem;
            border-radius: 999px;
            background: var(--twyxtco-bar-soft);
        }

        .twyxtco-analytics-mini-bar span {
            display: block;
            height: 100%;
            border-radius: inherit;
            background: var(--twyxtco-bar);
        }

        .twyxtco-analytics-breakdowns {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.75rem;
        }

        .twyxtco-analytics-breakdown-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 0.75rem;
            align-items: center;
            padding: 0.45rem 0;
        }

        .twyxtco-analytics-empty {
            border: 1px dashed var(--twyxtco-panel-border);
            border-radius: 0.65rem;
            padding: 1rem;
            color: var(--twyxtco-text-muted);
        }

        @media (max-width: 1200px) {
            .twyxtco-analytics-controls,
            .twyxtco-analytics-breakdowns {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .twyxtco-analytics-metrics {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
            .twyxtco-analytics-controls,
            .twyxtco-analytics-metrics,
            .twyxtco-analytics-breakdowns {
                grid-template-columns: 1fr;
            }

            .twyxtco-analytics-row,
            .twyxtco-analytics-row--three {
                grid-template-columns: minmax(0, 1fr);
            }

            .twyxtco-analytics-stat {
                text-align: left;
            }
        }
    </style>

    <div class="twyxtco-analytics space-y-6">
        <div class="twyxtco-analytics-card">
            <div class="twyxtco-analytics-controls">
                <div class="twyxtco-analytics-control">
                    <label for="analytics-range">Date range</label>
                    <select id="analytics-range" wire:model.live="range">
                        @foreach ($this->rangeOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="twyxtco-analytics-control">
                    <label for="analytics-path">Page</label>
                    <select id="analytics-path" wire:model.live="path">
                        @foreach ($this->pathOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="twyxtco-analytics-control">
                    <label for="analytics-source">Source</label>
                    <select id="analytics-source" wire:model.live="source">
                        @foreach ($this->sourceOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="twyxtco-analytics-control">
                    <label for="analytics-device">Device</label>
                    <select id="analytics-device" wire:model.live="device">
                        @foreach ($this->deviceOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="twyxtco-analytics-control">
                    <label for="analytics-country">Country</label>
                    <select id="analytics-country" wire:model.live="country">
                        @foreach ($this->countryOptions() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="twyxtco-analytics-metrics">
            @foreach ($metrics as $metric)
                <div class="twyxtco-analytics-metric">
                    <div class="twyxtco-analytics-section-label">{{ $metric['label'] }}</div>
                    <div class="twyxtco-analytics-metric__value">{{ $metric['value'] }}</div>
                    <div class="twyxtco-analytics-muted text-sm">{{ $metric['meta'] }}</div>
                </div>
            @endforeach
        </div>

        <div class="twyxtco-analytics-card">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">Daily Traffic</h2>
                    <p class="text-sm twyxtco-analytics-muted">Views by day for the selected filters.</p>
                </div>
            </div>

            <div class="twyxtco-analytics-trend" aria-label="Daily traffic trend">
                @foreach ($trend as $day)
                    <div title="{{ $day['day'] }}: {{ number_format($day['views']) }} views, {{ number_format($day['visitors']) }} visitors">
                        <div class="twyxtco-analytics-trend__bar">
                            <span
                                class="twyxtco-analytics-trend__fill"
                                style="height: {{ $this->percentageWidth($day['views'], $maxTrendViews) }}"
                            ></span>
                        </div>
                        <div class="mt-1 truncate text-center text-[0.65rem] twyxtco-analytics-muted">{{ $day['label'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <div class="twyxtco-analytics-card">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Top Pages</h2>
                <p class="mb-4 text-sm twyxtco-analytics-muted">Ranked by page views for the selected filters.</p>

                @if ($topPages->isEmpty())
                    <div class="twyxtco-analytics-empty">No page views match the current filters.</div>
                @else
                    <div class="twyxtco-analytics-table">
                        @foreach ($topPages as $page)
                            <div class="twyxtco-analytics-row">
                                <div class="min-w-0">
                                    <div class="twyxtco-analytics-title">{{ $page->page_title ?: $page->path }}</div>
                                    <div class="text-sm twyxtco-analytics-muted">{{ $page->path }}</div>
                                </div>
                                <div class="twyxtco-analytics-stat">
                                    <strong>{{ number_format($page->views) }}</strong>
                                    <span class="text-xs twyxtco-analytics-muted">Views</span>
                                </div>
                                <div class="twyxtco-analytics-stat">
                                    <strong>{{ number_format($page->visitors) }}</strong>
                                    <span class="text-xs twyxtco-analytics-muted">Visitors</span>
                                </div>
                                <div>
                                    <div class="twyxtco-analytics-mini-bar">
                                        <span style="width: {{ $this->percentageWidth($page->views, $maxPageViews) }}"></span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="twyxtco-analytics-card">
                <h2 class="text-base font-semibold text-gray-950 dark:text-white">Referrer Traffic</h2>
                <p class="mb-4 text-sm twyxtco-analytics-muted">External domains sending visitors to the site.</p>

                @if ($topReferrers->isEmpty())
                    <div class="twyxtco-analytics-empty">No external referrers match the current filters.</div>
                @else
                    <div class="twyxtco-analytics-table">
                        @foreach ($topReferrers as $referrer)
                            <div class="twyxtco-analytics-row twyxtco-analytics-row--three">
                                <div class="min-w-0">
                                    <div class="twyxtco-analytics-title">{{ $referrer->referrer_domain }}</div>
                                </div>
                                <div class="twyxtco-analytics-stat">
                                    <strong>{{ number_format($referrer->views) }}</strong>
                                    <span class="text-xs twyxtco-analytics-muted">Views</span>
                                </div>
                                <div>
                                    <div class="twyxtco-analytics-mini-bar">
                                        <span style="width: {{ $this->percentageWidth($referrer->views, $maxReferrerViews) }}"></span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="twyxtco-analytics-breakdowns">
            @foreach ([
                'Devices' => $devices,
                'Browsers' => $browsers,
                'Platforms' => $platforms,
            ] as $label => $rows)
                @php($maxBreakdownViews = $this->maxValue($rows, 'views'))
                <div class="twyxtco-analytics-card">
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">{{ $label }}</h2>
                    @if ($rows->isEmpty())
                        <div class="twyxtco-analytics-empty mt-4">No data yet.</div>
                    @else
                        <div class="mt-3">
                            @foreach ($rows as $row)
                                <div class="twyxtco-analytics-breakdown-row">
                                    <div class="min-w-0">
                                        <div class="twyxtco-analytics-kicker">{{ $row->label }}</div>
                                        <div class="twyxtco-analytics-mini-bar mt-1">
                                            <span style="width: {{ $this->percentageWidth($row->views, $maxBreakdownViews) }}"></span>
                                        </div>
                                    </div>
                                    <div class="twyxtco-analytics-stat">
                                        <strong>{{ number_format($row->views) }}</strong>
                                        <span class="text-xs twyxtco-analytics-muted">Views</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="twyxtco-analytics-breakdowns">
            @foreach ([
                'Countries' => $countries,
                'Regions' => $regions,
                'Cities' => $cities,
            ] as $label => $rows)
                @php($maxBreakdownViews = $this->maxValue($rows, 'views'))
                <div class="twyxtco-analytics-card">
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">{{ $label }}</h2>
                    @if ($rows->isEmpty())
                        <div class="twyxtco-analytics-empty mt-4">No geo data yet.</div>
                    @else
                        <div class="mt-3">
                            @foreach ($rows as $row)
                                <div class="twyxtco-analytics-breakdown-row">
                                    <div class="min-w-0">
                                        <div class="twyxtco-analytics-kicker">{{ $row->label }}</div>
                                        <div class="twyxtco-analytics-mini-bar mt-1">
                                            <span style="width: {{ $this->percentageWidth($row->views, $maxBreakdownViews) }}"></span>
                                        </div>
                                    </div>
                                    <div class="twyxtco-analytics-stat">
                                        <strong>{{ number_format($row->views) }}</strong>
                                        <span class="text-xs twyxtco-analytics-muted">Views</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="twyxtco-analytics-card">
            <h2 class="text-base font-semibold text-gray-950 dark:text-white">Recent Page Views</h2>
            <p class="mb-4 text-sm twyxtco-analytics-muted">Latest tracked public HTML page views matching the filters.</p>

            @if ($recentViews->isEmpty())
                <div class="twyxtco-analytics-empty">No recent page views match the current filters.</div>
            @else
                <div class="twyxtco-analytics-table">
                    @foreach ($recentViews as $view)
                        <div class="twyxtco-analytics-row">
                            <div class="min-w-0">
                                <div class="twyxtco-analytics-title">{{ $view->page_title ?: $view->path }}</div>
                                <div class="text-sm twyxtco-analytics-muted">{{ $view->path }}</div>
                                @if ($view->referrer_domain)
                                    <div class="text-xs twyxtco-analytics-muted">From {{ $view->referrer_domain }}</div>
                                @endif
                                @if ($view->city_name || $view->region_name || $view->country_name)
                                    <div class="text-xs twyxtco-analytics-muted">
                                        {{ collect([$view->city_name, $view->region_name, $view->country_name])->filter()->implode(', ') }}
                                    </div>
                                @endif
                            </div>
                            <div class="twyxtco-analytics-stat">
                                <strong>{{ $view->device_type ?: 'Unknown' }}</strong>
                                <span class="text-xs twyxtco-analytics-muted">Device</span>
                            </div>
                            <div class="twyxtco-analytics-stat">
                                <strong>{{ $view->browser ?: 'Unknown' }}</strong>
                                <span class="text-xs twyxtco-analytics-muted">Browser</span>
                            </div>
                            <div class="twyxtco-analytics-stat">
                                <strong>{{ $view->viewed_at?->diffForHumans() }}</strong>
                                <span class="text-xs twyxtco-analytics-muted">Viewed</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
