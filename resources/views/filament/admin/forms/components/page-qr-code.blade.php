@php
    /** @var \App\Models\PageQrCode|null $qrCode */
    $qrCode = $qrCode ?? null;
@endphp

<div class="space-y-2">
    <div class="fi-fo-field-label-col">
        <div class="fi-fo-field-label-ctn">
            <span class="fi-fo-field-label">
                <span class="fi-fo-field-label-content">QR Code</span>
            </span>
        </div>
    </div>

    <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
        @if ($qrCode)
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <div
                    class="rounded-md border border-gray-200 bg-white dark:border-gray-700"
                    style="margin-top: 10px;width: 36px; height: 36px; padding: 3px; display: flex; align-items: center; justify-content: center; flex: 0 0 auto;"
                >
                    <a
                        href="{{ $qrCode->pngUrl() }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        aria-label="Open QR code PNG in a new window"
                    >
                        <img
                            src="{{ $qrCode->pngUrl() }}"
                            alt="QR code for {{ $qrCode->url }}"
                            width="30"
                            height="30"
                            style="display: block; width: 32px; height: 32px; max-width: 32px; max-height: 32px;"
                        >
                    </a>
                </div>

                <div style="display: flex; align-items: center; gap: 3px; margin-top:5px;">
                    <x-filament::icon-button
                        tag="a"
                        color="gray"
                        icon="heroicon-o-arrow-top-right-on-square"
                        label="View Page"
                        tooltip="View Page"
                        href="{{ $qrCode->url }}"
                        target="_blank"
                    />

                    <x-filament::icon-button
                        tag="a"
                        color="gray"
                        icon="heroicon-o-photo"
                        label="Download PNG"
                        tooltip="Download PNG"
                        href="{{ $qrCode->pngUrl() }}"
                        download="{{ $qrCode->pngDownloadName() }}"
                    />

                    <x-filament::icon-button
                        tag="a"
                        color="gray"
                        icon="heroicon-o-code-bracket-square"
                        label="Download SVG"
                        tooltip="Download SVG"
                        href="{{ $qrCode->svgUrl() }}"
                        download="{{ $qrCode->svgDownloadName() }}"
                    />
                </div>
            </div>
        @else
            <p class="text-sm text-gray-600 dark:text-gray-300">
                Save this page to generate its QR code.
            </p>
        @endif
    </div>
</div>
