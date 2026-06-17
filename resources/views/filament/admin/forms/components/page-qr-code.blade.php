@php
    /** @var \App\Models\PageQrCode|null $qrCode */
    $qrCode = $qrCode ?? null;
@endphp

<div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
    @if ($qrCode)
        <div class="grid gap-4 md:grid-cols-[160px_minmax(0,1fr)]">
            <div class="rounded-md border border-gray-200 bg-white p-3 dark:border-gray-700">
                <img
                    src="{{ $qrCode->pngUrl() }}"
                    alt="QR code for {{ $qrCode->url }}"
                    class="h-auto w-full"
                >
            </div>

            <div class="min-w-0 space-y-3">
                <div>
                    <div class="text-sm font-medium text-gray-950 dark:text-white">Encoded URL</div>
                    <a
                        href="{{ $qrCode->url }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="break-all text-sm text-primary-600 hover:underline dark:text-primary-400"
                    >
                        {{ $qrCode->url }}
                    </a>
                </div>

                <div class="text-sm text-gray-600 dark:text-gray-300">
                    Generated {{ $qrCode->generated_at?->toDayDateTimeString() }}
                </div>

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
