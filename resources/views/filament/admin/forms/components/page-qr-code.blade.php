@php
    /** @var \App\Models\PageQrCode|null $qrCode */
    $qrCode = $qrCode ?? null;
@endphp

<div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
    @if ($qrCode)
        <div class="flex flex-wrap items-start gap-4">
            <div
                class="rounded-md border border-gray-200 bg-white dark:border-gray-700"
                style="width: 72px; height: 72px; padding: 6px; display: flex; align-items: center; justify-content: center; flex: 0 0 auto;"
            >
                <img
                    src="{{ $qrCode->pngUrl() }}"
                    alt="QR code for {{ $qrCode->url }}"
                    width="60"
                    height="60"
                    style="display: block; width: 60px; height: 60px; max-width: 60px; max-height: 60px;"
                >
            </div>

            <div class="min-w-0 flex-1 space-y-3">
                <div class="flex flex-wrap gap-2">
                    <x-filament::button
                        tag="a"
                        color="gray"
                        icon="heroicon-o-arrow-top-right-on-square"
                        href="{{ $qrCode->url }}"
                        target="_blank"
                    >
                        View Page
                    </x-filament::button>

                    <x-filament::button
                        tag="a"
                        color="gray"
                        icon="heroicon-o-arrow-down-tray"
                        href="{{ $qrCode->pngUrl() }}"
                        download
                    >
                        Download PNG
                    </x-filament::button>

                    <x-filament::button
                        tag="a"
                        color="gray"
                        icon="heroicon-o-arrow-down-tray"
                        href="{{ $qrCode->svgUrl() }}"
                        download
                    >
                        Download SVG
                    </x-filament::button>
                </div>
            </div>
        </div>
    @else
        <p class="text-sm text-gray-600 dark:text-gray-300">
            Save this page to generate its QR code.
        </p>
    @endif
</div>
