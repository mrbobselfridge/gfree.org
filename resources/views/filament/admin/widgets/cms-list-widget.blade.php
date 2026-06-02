<x-filament-widgets::widget
    class="gfree-dashboard-widget"
    data-gfree-dashboard-widget="{{ $widgetKey }}"
>
    <x-filament::section>
        <div class="gfree-dashboard-widget-header flex items-start justify-between gap-4">
            <div class="min-w-0">
                <div class="flex min-w-0 items-center gap-2">
                    <button
                        type="button"
                        class="gfree-dashboard-widget-drag-handle shrink-0"
                        title="Move {{ $heading }}"
                        aria-label="Move {{ $heading }}"
                    >
                        Move
                    </button>

                    <h2 class="min-w-0 text-base font-semibold leading-6 text-gray-950 dark:text-white">
                        {{ $heading }}
                    </h2>
                </div>

                @if (filled($description))
                    <p
                        class="mt-1 text-sm text-gray-500 dark:text-gray-400"
                        data-gfree-dashboard-widget-description
                    >
                        {{ $description }}
                    </p>
                @endif
            </div>

            <div class="flex shrink-0 items-center gap-2">
                @if (filled($actionLabel) && filled($actionUrl))
                    <a
                        href="{{ $actionUrl }}"
                        class="text-sm font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
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

        <div class="mt-4 divide-y divide-gray-200 dark:divide-gray-800" data-gfree-dashboard-widget-body>
            @forelse ($rows as $row)
                <div class="flex items-center gap-3 py-3.5 first:pt-0 last:pb-0">
                    @if (filled($row['imageUrl'] ?? null))
                        <img
                            src="{{ $row['imageUrl'] }}"
                            alt=""
                            class="h-12 w-16 shrink-0 rounded-md object-cover ring-1 ring-gray-950/10 dark:ring-white/10"
                            loading="lazy"
                        >
                    @endif

                    <div class="min-w-0 flex-1">
                        <div class="mb-1">
                            <span class="inline-flex rounded-md bg-primary-50 px-2 py-0.5 text-[0.6875rem] font-bold uppercase tracking-wide text-primary-700 ring-1 ring-primary-600/15 dark:bg-primary-400/10 dark:text-primary-300 dark:ring-primary-400/20">
                                {{ $row['type'] }}
                            </span>
                        </div>

                        <h3 class="min-w-0">
                            @if (filled($row['url'] ?? null))
                                <a
                                    href="{{ $row['url'] }}"
                                    class="block truncate text-sm font-semibold text-gray-950 hover:text-primary-600 dark:text-white dark:hover:text-primary-400"
                                    wire:navigate
                                >
                                    {{ $row['title'] }}
                                </a>
                            @else
                                <span class="block truncate text-sm font-semibold text-gray-950 dark:text-white">
                                    {{ $row['title'] }}
                                </span>
                            @endif
                        </h3>

                        @if (filled($row['meta'] ?? null))
                            <p class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400" title="{{ $row['meta'] }}">
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
                <p class="py-4 text-sm text-gray-500 dark:text-gray-400">
                    {{ $emptyMessage }}
                </p>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
