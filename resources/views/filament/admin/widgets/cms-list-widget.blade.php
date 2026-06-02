<x-filament-widgets::widget
    class="gfree-dashboard-widget"
    data-gfree-dashboard-widget="{{ $widgetKey }}"
>
    <x-filament::section>
        <div class="gfree-dashboard-widget-shell">
            <div class="gfree-dashboard-widget-controls flex items-center justify-between gap-3">
                <button
                    type="button"
                    class="gfree-dashboard-widget-drag-handle"
                    title="Move {{ $heading }}"
                    aria-label="Move {{ $heading }}"
                >
                    Move
                </button>

                <div class="flex shrink-0 items-center gap-2">
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
                        Collapse
                    </button>
                </div>
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
                            <a href="{{ $row['url'] }}" class="shrink-0" wire:navigate>
                                <x-filament::badge :color="$row['statusColor'] ?? 'gray'">
                                    {{ $row['status'] }}
                                </x-filament::badge>
                            </a>
                        @else
                            <x-filament::badge :color="$row['statusColor'] ?? 'gray'" class="shrink-0">
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
