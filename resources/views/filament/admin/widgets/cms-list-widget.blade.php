<x-filament-widgets::widget
    class="gfree-dashboard-widget"
    data-gfree-dashboard-widget="{{ $widgetKey }}"
>
    <x-filament::section>
        <div class="gfree-dashboard-widget-shell">
            <div class="gfree-dashboard-widget-controls">
                @if (filled($actionLabel) && filled($actionUrl))
                    <a
                        href="{{ $actionUrl }}"
                        class="gfree-dashboard-widget-action"
                        wire:navigate
                    >
                        {{ $actionLabel }}
                    </a>
                @endif

                <button
                    type="button"
                    class="gfree-dashboard-widget-collapse"
                    title="Collapse {{ $heading }}"
                    aria-label="Collapse {{ $heading }}"
                    aria-expanded="true"
                    data-gfree-dashboard-widget-collapse
                >
                    <svg
                        class="gfree-dashboard-widget-control-icon gfree-dashboard-widget-collapse-icon-expanded"
                        aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.8"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="m18 15-6-6-6 6" />
                    </svg>
                    <svg
                        class="gfree-dashboard-widget-control-icon gfree-dashboard-widget-collapse-icon-collapsed"
                        aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.8"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                    </svg>
                </button>

                <button
                    type="button"
                    class="gfree-dashboard-widget-drag-handle"
                    title="Move {{ $heading }}"
                    aria-label="Move {{ $heading }}"
                >
                    <svg
                        class="gfree-dashboard-widget-control-icon"
                        aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.8"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18m0-18 3 3m-3-3-3 3m3 15 3-3m-3 3-3-3M3 12h18m-18 0 3-3m-3 3 3 3m15-3-3-3m3 3-3 3" />
                    </svg>
                </button>
            </div>

            <div class="gfree-dashboard-widget-header">
                <h2 class="gfree-dashboard-widget-title">
                    {{ $heading }}
                </h2>

                @if (filled($description))
                    <p
                        class="gfree-dashboard-widget-description"
                        data-gfree-dashboard-widget-description
                    >
                        {{ $description }}
                    </p>
                @endif
            </div>
        </div>

        <div class="gfree-dashboard-widget-body" data-gfree-dashboard-widget-body>
            @forelse ($rows as $row)
                <div class="gfree-dashboard-widget-row">
                    @if (filled($row['imageUrl'] ?? null))
                        <img
                            src="{{ $row['imageUrl'] }}"
                            alt=""
                            class="gfree-dashboard-widget-row-image"
                            loading="lazy"
                        >
                    @endif

                    <div class="min-w-0 flex-1">
                        <div class="mb-1.5">
                            <span class="gfree-dashboard-widget-type">
                                {{ $row['type'] }}
                            </span>
                        </div>

                        <h3 class="min-w-0">
                            @if (filled($row['url'] ?? null))
                                <a
                                    href="{{ $row['url'] }}"
                                    class="gfree-dashboard-widget-row-title"
                                    wire:navigate
                                >
                                    {{ $row['title'] }}
                                </a>
                            @else
                                <span class="gfree-dashboard-widget-row-title">
                                    {{ $row['title'] }}
                                </span>
                            @endif
                        </h3>

                        @if (filled($row['meta'] ?? null))
                            <p class="gfree-dashboard-widget-row-meta" title="{{ $row['meta'] }}">
                                {{ $row['meta'] }}
                            </p>
                        @endif
                    </div>

                    @if (filled($row['status'] ?? null))
                        @if (filled($row['url'] ?? null))
                            <a href="{{ $row['url'] }}" class="gfree-dashboard-widget-row-status" wire:navigate>
                                <x-filament::badge :color="$row['statusColor'] ?? 'gray'">
                                    {{ $row['status'] }}
                                </x-filament::badge>
                            </a>
                        @else
                            <x-filament::badge :color="$row['statusColor'] ?? 'gray'" class="gfree-dashboard-widget-row-status">
                                {{ $row['status'] }}
                            </x-filament::badge>
                        @endif
                    @endif
                </div>
            @empty
                <p class="gfree-dashboard-widget-empty">
                    {{ $emptyMessage }}
                </p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
