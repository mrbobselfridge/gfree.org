<x-filament-panels::page>
    @php
        $images = $this->libraryTab === 'images' && $this->canAccessImages() ? $this->getImages() : collect();
        $totalImages = $this->libraryTab === 'images' && $this->canAccessImages() ? $this->getTotalImageCount() : 0;
        $sortOptions = $this->getSortOptions();
    @endphp

    <style>
        .twyxtco-library-tabs {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid rgb(229 231 235);
            border-radius: 0.75rem;
            background: white;
            padding: 0.625rem;
        }

        .dark .twyxtco-library-tabs {
            border-color: rgb(31 41 55);
            background: rgb(17 24 39);
        }

        .twyxtco-library-tabs__label {
            padding: 0 0.25rem;
            color: rgb(75 85 99);
            font-size: 0.875rem;
            font-weight: 700;
        }

        .dark .twyxtco-library-tabs__label {
            color: rgb(209 213 219);
        }

        .twyxtco-library-tabs__button {
            border-radius: 0.5rem;
            padding: 0.45rem 0.75rem;
            color: rgb(75 85 99);
            font-size: 0.8125rem;
            font-weight: 700;
        }

        .twyxtco-library-tabs__button:hover,
        .twyxtco-library-tabs__button:focus {
            background: rgb(243 244 246);
            color: rgb(17 24 39);
        }

        .dark .twyxtco-library-tabs__button {
            color: rgb(209 213 219);
        }

        .dark .twyxtco-library-tabs__button:hover,
        .dark .twyxtco-library-tabs__button:focus {
            background: rgb(31 41 55);
            color: white;
        }

        .twyxtco-library-tabs__button--active {
            background: rgb(217 119 6);
            color: white;
        }

        .twyxtco-library-tabs__button--active:hover,
        .twyxtco-library-tabs__button--active:focus,
        .dark .twyxtco-library-tabs__button--active,
        .dark .twyxtco-library-tabs__button--active:hover,
        .dark .twyxtco-library-tabs__button--active:focus {
            background: rgb(217 119 6);
            color: white;
        }

        .twyxtco-media-summary {
            border: 1px solid rgb(229 231 235);
            border-radius: 0.75rem;
            background: white;
            padding: 1rem;
        }

        .dark .twyxtco-media-summary {
            border-color: rgb(31 41 55);
            background: rgb(17 24 39);
        }

        .twyxtco-media-controls {
            display: grid;
            grid-template-columns: minmax(220px, 1fr) minmax(180px, 260px);
            gap: 0.75rem;
            align-items: end;
            margin: 1rem 0 1.25rem;
        }

        .twyxtco-media-control label {
            display: block;
            margin-bottom: 0.25rem;
            color: rgb(75 85 99);
            font-size: 0.75rem;
            font-weight: 650;
        }

        .dark .twyxtco-media-control label {
            color: rgb(209 213 219);
        }

        .twyxtco-media-control input,
        .twyxtco-media-control select {
            width: 100%;
            border: 1px solid rgb(209 213 219);
            border-radius: 0.5rem;
            background: white;
            padding: 0.5rem 0.75rem;
            color: rgb(17 24 39);
            font-size: 0.875rem;
        }

        .dark .twyxtco-media-control input,
        .dark .twyxtco-media-control select {
            border-color: rgb(55 65 81);
            background: rgb(3 7 18);
            color: white;
        }

        .twyxtco-media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 0.75rem;
        }

        .twyxtco-media-card {
            overflow: hidden;
            border: 1px solid rgb(229 231 235);
            border-radius: 0.75rem;
            background: white;
        }

        .dark .twyxtco-media-card {
            border-color: rgb(31 41 55);
            background: rgb(17 24 39);
        }

        .twyxtco-media-card__image {
            display: block;
            width: 100%;
            height: 5.25rem;
            object-fit: contain;
            background: rgb(243 244 246);
        }

        .dark .twyxtco-media-card__image {
            background: rgb(3 7 18);
        }

        .twyxtco-media-card__body {
            padding: 0.625rem;
        }

        .twyxtco-media-card__title,
        .twyxtco-media-card__path,
        .twyxtco-media-card__meta {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .twyxtco-media-card__title {
            color: rgb(17 24 39);
            font-size: 0.75rem;
            font-weight: 700;
        }

        .dark .twyxtco-media-card__title {
            color: white;
        }

        .twyxtco-media-card__path,
        .twyxtco-media-card__meta {
            color: rgb(107 114 128);
            font-size: 0.6875rem;
        }

        .twyxtco-media-card__stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.375rem;
            margin-top: 0;
            color: rgb(107 114 128);
            font-size: 0.6875rem;
        }

        .twyxtco-media-card__stats dd {
            margin: 0;
        }

        .twyxtco-media-card__usage {
            margin-top: 0.35rem;
            padding: 0;
            color: rgb(107 114 128);
            font-size: 0.6875rem;
            line-height: 1.2;
        }

        .twyxtco-media-card__usage-list {
            margin: 0;
            padding-left: 0.875rem;
            list-style: disc;
        }

        .twyxtco-media-card__usage li,
        .twyxtco-media-card__usage p {
            margin: 0;
            font-size: inherit;
            line-height: inherit;
        }

        .twyxtco-media-card__usage-line {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .twyxtco-media-card__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem;
            justify-content: center;
            margin-top: 0.45rem;
            font-size: 0.75rem;
            text-align: center;
        }

        .twyxtco-media-card__actions a,
        .twyxtco-media-card__actions button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.6rem;
            height: 1.6rem;
            color: rgb(217 119 6);
            font-weight: 650;
        }

        .twyxtco-media-card__actions svg {
            width: 1rem;
            height: 1rem;
        }

        @media (max-width: 640px) {
            .twyxtco-media-controls {
                grid-template-columns: 1fr;
            }

            .twyxtco-media-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }
    </style>

    <div class="space-y-6">
        <div class="twyxtco-library-tabs" aria-label="Library sections">
            <span class="twyxtco-library-tabs__label">Library:</span>

            @if ($this->canAccessImages())
                <button
                    type="button"
                    wire:click="setLibraryTab('images')"
                    class="twyxtco-library-tabs__button {{ $this->libraryTab === 'images' ? 'twyxtco-library-tabs__button--active' : '' }}"
                    aria-pressed="{{ $this->libraryTab === 'images' ? 'true' : 'false' }}"
                >
                    Images
                </button>
            @endif

            @if ($this->canAccessFiles())
                <button
                    type="button"
                    wire:click="setLibraryTab('files')"
                    class="twyxtco-library-tabs__button {{ $this->libraryTab === 'files' ? 'twyxtco-library-tabs__button--active' : '' }}"
                    aria-pressed="{{ $this->libraryTab === 'files' ? 'true' : 'false' }}"
                >
                    Files
                </button>
            @endif
        </div>

        @if ($this->libraryTab === 'files' && $this->canAccessFiles())
            {{ $this->table }}
        @elseif ($this->canAccessImages())
            <div class="twyxtco-media-summary">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-gray-950 dark:text-white">Uploaded images</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            {{ $images->count() }} of {{ $totalImages }} {{ \Illuminate\Support\Str::plural('image', $totalImages) }} shown.
                        </p>
                    </div>
                </div>
            </div>

            <div class="twyxtco-media-controls">
                <div class="twyxtco-media-control">
                    <label for="media-search">Search</label>
                    <input
                        id="media-search"
                        type="search"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Search path, filename, or content area"
                    >
                </div>

                <div class="twyxtco-media-control">
                    <label for="media-sort">Sort by</label>
                    <select id="media-sort" wire:model.live="sort">
                        @foreach ($sortOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            @if ($images->isEmpty())
                <div class="rounded-xl border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                    No images match the current search.
                </div>
            @else
                <div class="twyxtco-media-grid">
                    @foreach ($images as $image)
                        <article class="twyxtco-media-card" title="{{ $image['path'] }}">
                        <a href="{{ $image['url'] }}" target="_blank" rel="noreferrer" title="Open">
                            <img
                                src="{{ $image['url'] }}"
                                alt=""
                                class="twyxtco-media-card__image"
                                loading="lazy"
                            >
                        </a>

                        <div class="twyxtco-media-card__body">
                            <dl class="twyxtco-media-card__stats">
                                <div>
                                    <dd>{{ $image['size_for_humans'] }}</dd>
                                </div>
                                <div>
                                    <dd>{{ $image['dimensions_for_humans'] ?? 'Unknown' }}</dd>
                                </div>
                            </dl>

                            <div class="twyxtco-media-card__usage">
                                @if ($image['usage_count'] > 0)
                                    <ul class="twyxtco-media-card__usage-list">
                                        @foreach (array_slice($image['usage'], 0, 4) as $usage)
                                            @php($fullUsageText = "{$usage['label']} | {$usage['detail']}")
                                            @php($shortUsageText = "{$usage['short_label']} | {$usage['detail']}")
                                            <li>
                                                <span class="twyxtco-media-card__usage-line" title="{{ $fullUsageText }}">{{ str($shortUsageText)->limit(30) }}</span>
                                            </li>
                                        @endforeach
                                    </ul>

                                    @if ($image['usage_count'] > 4)
                                        <p class="mt-1 text-gray-500 dark:text-gray-400">
                                            + {{ $image['usage_count'] - 4 }} more
                                        </p>
                                    @endif
                                @else
                                    <p title="This image is not currently selected in any tracked image field or content block.">
                                        Unused
                                    </p>
                                @endif
                            </div>

                            <div class="twyxtco-media-card__actions">
                                <a
                                    href="{{ $image['url'] }}"
                                    target="_blank"
                                    rel="noreferrer"
                                    title="Open"
                                    aria-label="Open"
                                >
                                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H18m0 0v4.5M18 6l-7.5 7.5M6 7.5v10.5h10.5" />
                                    </svg>
                                </a>
                                <a
                                    href="{{ $image['url'] }}"
                                    download="{{ $image['name'] }}"
                                    title="Download"
                                    aria-label="Download"
                                >
                                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v11m0 0 4-4m-4 4-4-4M5 19h14" />
                                    </svg>
                                </a>
                                <button
                                    type="button"
                                    x-data
                                    x-on:click="navigator.clipboard.writeText(@js($image['url']))"
                                    title="Copy URL"
                                    aria-label="Copy URL"
                                >
                                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 8V5.5A1.5 1.5 0 0 1 9.5 4h8A1.5 1.5 0 0 1 19 5.5v8A1.5 1.5 0 0 1 17.5 15H15M6.5 9h8A1.5 1.5 0 0 1 16 10.5v8a1.5 1.5 0 0 1-1.5 1.5h-8A1.5 1.5 0 0 1 5 18.5v-8A1.5 1.5 0 0 1 6.5 9Z" />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    wire:click="mountAction('replaceImage', { path: @js($image['path']) })"
                                    title="Replace"
                                    aria-label="Replace"
                                >
                                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.9 4.6 2.5 2.5M4 20h4.2L18.7 9.5a1.8 1.8 0 0 0 0-2.5L17 5.3a1.8 1.8 0 0 0-2.5 0L4 15.8V20Z" />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    wire:click="mountAction('deleteImage', { path: @js($image['path']) })"
                                    title="Delete"
                                    aria-label="Delete"
                                    class="text-danger-600"
                                >
                                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5.5A1.5 1.5 0 0 1 10.5 4h3A1.5 1.5 0 0 1 15 5.5V7m2 0-.7 12.2A1.5 1.5 0 0 1 14.8 20H9.2a1.5 1.5 0 0 1-1.5-1.4L7 7m3 3v7m4-7v7" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        </article>
                    @endforeach
                </div>
            @endif
        @endif
    </div>
</x-filament-panels::page>
