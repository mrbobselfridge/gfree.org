<x-filament-widgets::widget>
    <x-filament::section>
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-base font-semibold leading-6 text-gray-950 dark:text-white">
                    {{ $heading }}
                </h2>

                @if (filled($description))
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        {{ $description }}
                    </p>
                @endif
            </div>

            @if (filled($actionLabel) && filled($actionUrl))
                <a
                    href="{{ $actionUrl }}"
                    class="shrink-0 text-sm font-semibold text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
                    wire:navigate
                >
                    {{ $actionLabel }}
                </a>
            @endif
        </div>

        <div class="mt-4 divide-y divide-gray-200 dark:divide-gray-800">
            @forelse ($rows as $row)
                <div class="flex items-center gap-3 py-3 first:pt-0 last:pb-0">
                    @if (filled($row['imageUrl'] ?? null))
                        <img
                            src="{{ $row['imageUrl'] }}"
                            alt=""
                            class="h-12 w-16 shrink-0 rounded-md object-cover ring-1 ring-gray-950/10 dark:ring-white/10"
                            loading="lazy"
                        >
                    @endif

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="shrink-0 rounded-md bg-gray-100 px-1.5 py-0.5 text-[0.6875rem] font-semibold uppercase tracking-wide text-gray-600 dark:bg-gray-800 dark:text-gray-300">
                                {{ $row['type'] }}
                            </span>

                            @if (filled($row['url'] ?? null))
                                <a
                                    href="{{ $row['url'] }}"
                                    class="truncate text-sm font-semibold text-gray-950 hover:text-primary-600 dark:text-white dark:hover:text-primary-400"
                                    wire:navigate
                                >
                                    {{ $row['title'] }}
                                </a>
                            @else
                                <span class="truncate text-sm font-semibold text-gray-950 dark:text-white">
                                    {{ $row['title'] }}
                                </span>
                            @endif
                        </div>

                        @if (filled($row['meta'] ?? null))
                            <p class="mt-1 truncate text-xs text-gray-500 dark:text-gray-400" title="{{ $row['meta'] }}">
                                {{ $row['meta'] }}
                            </p>
                        @endif
                    </div>

                    @if (filled($row['status'] ?? null))
                        <x-filament::badge :color="$row['statusColor'] ?? 'gray'" class="shrink-0">
                            {{ $row['status'] }}
                        </x-filament::badge>
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
