<x-filament-panels::page>
    @php
        $imageResults = $this->canAccessImages() ? $this->getImageResults() : [
            'items' => collect(),
            'total' => 0,
            'filtered_total' => 0,
            'has_more' => false,
        ];
        $images = $imageResults['items'];
        $totalImages = $imageResults['total'];
        $filteredImages = $imageResults['filtered_total'];
        $hasMoreImages = $imageResults['has_more'];
        $sortOptions = $this->getSortOptions();
        $addAction = $this->canAccessImages() ? 'uploadImages' : null;
        $selectedImageCount = $this->getSelectedImageCount();
        $allShownImagesSelected = $this->allShownImagesSelected();
    @endphp

    <style>
        .twyxtco-media-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            border: 1px solid rgb(229 231 235);
            border-radius: 0.75rem;
            background: white;
            padding: 0.625rem;
        }

        .dark .twyxtco-media-toolbar {
            border-color: rgb(31 41 55);
            background: rgb(17 24 39);
        }

        .twyxtco-media-toolbar__label {
            padding: 0 0.25rem;
            color: rgb(75 85 99);
            font-size: 0.875rem;
            font-weight: 700;
        }

        .dark .twyxtco-media-toolbar__label {
            color: rgb(209 213 219);
        }

        .twyxtco-media-toolbar__bulk {
            display: inline-flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.4rem;
            margin-left: auto;
        }

        .twyxtco-media-toolbar__select,
        .twyxtco-media-toolbar__count {
            display: inline-flex;
            align-items: center;
            min-height: 2rem;
            border-radius: 0.5rem;
            padding: 0 0.625rem;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .twyxtco-media-toolbar__select {
            border: 1px solid rgb(209 213 219);
            color: rgb(55 65 81);
        }

        .twyxtco-media-toolbar__select:hover,
        .twyxtco-media-toolbar__select:focus {
            border-color: rgb(217 119 6);
            color: rgb(180 83 9);
        }

        .dark .twyxtco-media-toolbar__select {
            border-color: rgb(55 65 81);
            color: rgb(209 213 219);
        }

        .twyxtco-media-toolbar__count {
            color: rgb(107 114 128);
        }

        .dark .twyxtco-media-toolbar__count {
            color: rgb(156 163 175);
        }

        .twyxtco-media-toolbar__add,
        .twyxtco-media-toolbar__delete {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2rem;
            height: 2rem;
            border-radius: 0.5rem;
            color: white;
        }

        .twyxtco-media-toolbar__add {
            background: rgb(217 119 6);
        }

        .twyxtco-media-toolbar__unsplash {
            width: auto;
            gap: 0.375rem;
            padding: 0 0.75rem;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .twyxtco-media-toolbar__upload {
            width: auto;
            gap: 0.375rem;
            padding: 0 0.75rem;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .twyxtco-media-toolbar__actions {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .twyxtco-media-toolbar__delete {
            background: rgb(220 38 38);
        }

        .twyxtco-media-toolbar__delete:disabled {
            cursor: not-allowed;
            background: rgb(209 213 219);
            color: rgb(107 114 128);
        }

        .twyxtco-media-toolbar__add:hover,
        .twyxtco-media-toolbar__add:focus {
            background: rgb(180 83 9);
        }

        .twyxtco-media-toolbar__delete:not(:disabled):hover,
        .twyxtco-media-toolbar__delete:not(:disabled):focus {
            background: rgb(185 28 28);
        }

        .twyxtco-media-toolbar__add svg,
        .twyxtco-media-toolbar__delete svg {
            width: 1rem;
            height: 1rem;
        }

        .twyxtco-media-summary {
            border: 1px solid rgb(229 231 235);
            border-radius: 0.75rem;
            background: white;
            margin-top: 0.625rem;
            padding: 0.375rem 0.75rem;
            text-align: center;
        }

        .twyxtco-media-summary__count {
            color: rgb(75 85 99);
            font-size: 0.875rem;
            font-weight: 700;
        }

        .dark .twyxtco-media-summary {
            border-color: rgb(31 41 55);
            background: rgb(17 24 39);
        }

        .dark .twyxtco-media-summary__count {
            color: rgb(209 213 219);
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
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .twyxtco-media-load-more {
            display: flex;
            justify-content: center;
            margin-top: 1rem;
        }

        .twyxtco-media-load-more button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            background: rgb(217 119 6);
            padding: 0.5rem 0.875rem;
            color: white;
            font-size: 0.875rem;
            font-weight: 700;
        }

        .twyxtco-media-load-more button:hover,
        .twyxtco-media-load-more button:focus {
            background: rgb(180 83 9);
        }

        .twyxtco-media-card {
            position: relative;
            overflow: hidden;
            border: 1px solid rgb(229 231 235);
            border-radius: 0.75rem;
            background: white;
        }

        .twyxtco-media-card--selected {
            border-color: rgb(217 119 6);
            box-shadow: 0 0 0 1px rgb(217 119 6);
        }

        .dark .twyxtco-media-card {
            border-color: rgb(31 41 55);
            background: rgb(17 24 39);
        }

        .dark .twyxtco-media-card--selected {
            border-color: rgb(245 158 11);
            box-shadow: 0 0 0 1px rgb(245 158 11);
        }

        .twyxtco-media-card__select {
            position: absolute;
            top: 0.5rem;
            left: 0.5rem;
            z-index: 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.75rem;
            height: 1.75rem;
            border-radius: 0.45rem;
            background: rgb(255 255 255 / 0.92);
            box-shadow: 0 1px 3px rgb(0 0 0 / 0.18);
        }

        .dark .twyxtco-media-card__select {
            background: rgb(17 24 39 / 0.9);
        }

        .twyxtco-media-card__select input {
            width: 1rem;
            height: 1rem;
            accent-color: rgb(217 119 6);
        }

        .twyxtco-media-card__image {
            display: block;
            width: 100%;
            height: 9rem;
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

        .twyxtco-media-card__tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem;
            margin-top: 0.35rem;
        }

        .twyxtco-media-card__tag {
            display: inline-flex;
            max-width: 100%;
            overflow: hidden;
            border: 1px solid rgb(229 231 235);
            border-radius: 999px;
            padding: 0.0625rem 0.375rem;
            color: rgb(75 85 99);
            font-size: 0.625rem;
            font-weight: 650;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dark .twyxtco-media-card__tag {
            border-color: rgb(55 65 81);
            color: rgb(209 213 219);
        }

        .twyxtco-media-card__usage-line {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .twyxtco-media-card__audit {
            display: grid;
            gap: 0.125rem;
            margin-top: 0.35rem;
            color: rgb(107 114 128);
            font-size: 0.6875rem;
            line-height: 1.2;
        }

        .twyxtco-media-card__audit span {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        a.twyxtco-media-card__usage-line {
            color: rgb(37 99 235);
            text-decoration: none;
        }

        a.twyxtco-media-card__usage-line:hover {
            text-decoration: underline;
        }

        .dark a.twyxtco-media-card__usage-line {
            color: rgb(147 197 253);
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
                grid-template-columns: 1fr;
            }

            .twyxtco-media-card__image {
                height: 11rem;
            }
        }

        @media (min-width: 641px) and (max-width: 1100px) {
            .twyxtco-media-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>

    <div>
        <div class="twyxtco-media-toolbar" aria-label="Media Library actions">
            @if ($addAction)
                <div class="twyxtco-media-toolbar__actions">
                    <button
                        type="button"
                        class="twyxtco-media-toolbar__add twyxtco-media-toolbar__unsplash"
                        wire:click="mountAction('importUnsplashImage')"
                        title="Copyright free images"
                        aria-label="Copyright free images"
                    >
                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M18 10.5a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z" />
                        </svg>
                        <span>Unsplash</span>
                    </button>
                    <button
                        type="button"
                        class="twyxtco-media-toolbar__add twyxtco-media-toolbar__upload"
                        wire:click="mountAction('{{ $addAction }}')"
                        title="Upload File"
                        aria-label="Upload File"
                    >
                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.9" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M7.5 7.5 12 3m0 0 4.5 4.5M12 3v13.5" />
                        </svg>
                        <span>Upload</span>
                    </button>
                </div>
            @endif

            @if ($images->isNotEmpty())
                <div class="twyxtco-media-toolbar__bulk" aria-label="Bulk image actions">
                    <button
                        type="button"
                        class="twyxtco-media-toolbar__select"
                        wire:click="toggleShownImages"
                    >
                        {{ $allShownImagesSelected ? 'Clear shown' : 'Select all shown' }}
                    </button>

                    <span class="twyxtco-media-toolbar__count">
                        {{ $selectedImageCount }} selected
                    </span>

                    <button
                        type="button"
                        class="twyxtco-media-toolbar__delete"
                        wire:click="mountAction('deleteSelectedImages')"
                        @disabled($selectedImageCount === 0)
                        title="Delete selected"
                        aria-label="Delete selected"
                    >
                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 7h12M9 7V5.5A1.5 1.5 0 0 1 10.5 4h3A1.5 1.5 0 0 1 15 5.5V7m2 0-.7 12.2A1.5 1.5 0 0 1 14.8 20H9.2a1.5 1.5 0 0 1-1.5-1.4L7 7m3 3v7m4-7v7" />
                        </svg>
                    </button>
                </div>
            @endif
        </div>

        @if ($this->canAccessImages())
            <div class="twyxtco-media-summary">
                <p class="twyxtco-media-summary__count">
                    @if (filled($this->search))
                        {{ $images->count() }} of {{ $filteredImages }} {{ \Illuminate\Support\Str::plural('Image', $filteredImages) }}
                    @else
                        {{ $images->count() }} of {{ $totalImages }} {{ \Illuminate\Support\Str::plural('Image', $totalImages) }}
                    @endif
                </p>
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
                        @php($isSelected = in_array($image['path'], $this->selectedImages, true))
                        <article @class(['twyxtco-media-card', 'twyxtco-media-card--selected' => $isSelected]) title="{{ $image['path'] }}">
                        <label class="twyxtco-media-card__select" title="Select image">
                            <input
                                type="checkbox"
                                wire:model.live="selectedImages"
                                value="{{ $image['path'] }}"
                                aria-label="Select {{ $image['display_title'] }}"
                            >
                        </label>

                        <a href="{{ $image['url'] }}" target="_blank" rel="noreferrer" title="Open">
                            <img
                                src="{{ $image['url'] }}"
                                alt=""
                                class="twyxtco-media-card__image"
                                loading="lazy"
                            >
                        </a>

                        <div class="twyxtco-media-card__body">
                            <span class="twyxtco-media-card__title" title="{{ $image['display_title'] }}">{{ $image['display_title'] }}</span>
                            <span class="twyxtco-media-card__path" title="{{ $image['path'] }}">{{ $image['path'] }}</span>

                            @if (filled($image['slug']))
                                <span class="twyxtco-media-card__meta" title="{{ $image['slug'] }}">/{{ $image['slug'] }}</span>
                            @endif

                            <dl class="twyxtco-media-card__stats">
                                <div>
                                    <dd>{{ $image['size_for_humans'] }}</dd>
                                </div>
                                <div>
                                    <dd>{{ $image['dimensions_for_humans'] ?? 'Unknown' }}</dd>
                                </div>
                            </dl>

                            <div class="twyxtco-media-card__audit">
                                <span title="{{ $image['created_at_for_humans'] ?? 'Not tracked' }}">
                                    Created: {{ $image['created_at_for_humans'] ?? 'Not tracked' }}
                                </span>
                                <span title="{{ $image['updated_at_for_humans'] ?? 'Not tracked' }}">
                                    Updated: {{ $image['updated_at_for_humans'] ?? 'Not tracked' }}
                                </span>
                                <span title="{{ $image['created_by_email'] ?? $image['created_by_name'] ?? 'Not tracked' }}">
                                    By: {{ $image['created_by_name'] ?? 'Not tracked' }}
                                </span>
                                @if (($image['source'] ?? null) === 'unsplash')
                                    <span title="{{ $image['source_url'] ?? 'Unsplash' }}">
                                        Source: Unsplash{{ filled($image['source_author_name'] ?? null) ? ' / '.$image['source_author_name'] : '' }}
                                    </span>
                                @endif
                            </div>

                            <div class="twyxtco-media-card__usage">
                                @if ($image['usage_count'] > 0)
                                    <ul class="twyxtco-media-card__usage-list">
                                        @foreach (array_slice($image['usage'], 0, 4) as $usage)
                                            @php($fullUsageText = "{$usage['label']} | {$usage['detail']}")
                                            @php($shortUsageText = "{$usage['short_label']} | {$usage['detail']}")
                                            <li>
                                                @if (filled($usage['edit_url'] ?? null))
                                                    <a
                                                        href="{{ $usage['edit_url'] }}"
                                                        class="twyxtco-media-card__usage-line"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        title="{{ $fullUsageText }}"
                                                    >
                                                        {{ str($shortUsageText)->limit(30) }}
                                                    </a>
                                                @else
                                                    <span class="twyxtco-media-card__usage-line" title="{{ $fullUsageText }}">{{ str($shortUsageText)->limit(30) }}</span>
                                                @endif
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

                            @if (filled($image['tags']))
                                <div class="twyxtco-media-card__tags" aria-label="Tags">
                                    @foreach ($image['tags'] as $tag)
                                        <span class="twyxtco-media-card__tag" title="{{ $tag }}">{{ $tag }}</span>
                                    @endforeach
                                </div>
                            @endif

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
                                    href="{{ $image['download_url'] }}"
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
                                    title="Copy link"
                                    aria-label="Copy link"
                                >
                                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 8V5.5A1.5 1.5 0 0 1 9.5 4h8A1.5 1.5 0 0 1 19 5.5v8A1.5 1.5 0 0 1 17.5 15H15M6.5 9h8A1.5 1.5 0 0 1 16 10.5v8a1.5 1.5 0 0 1-1.5 1.5h-8A1.5 1.5 0 0 1 5 18.5v-8A1.5 1.5 0 0 1 6.5 9Z" />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    wire:click="{{ $this->editImageMetadataClickHandler($image['path']) }}"
                                    title="Edit image"
                                    aria-label="Edit image"
                                >
                                    <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.9 4.6 2.5 2.5M4 20h4.2L18.7 9.5a1.8 1.8 0 0 0 0-2.5L17 5.3a1.8 1.8 0 0 0-2.5 0L4 15.8V20Z" />
                                    </svg>
                                </button>
                                <button
                                    type="button"
                                    wire:click="{{ $this->deleteImageClickHandler($image['path']) }}"
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

                @if ($hasMoreImages)
                    <div class="twyxtco-media-load-more">
                        <button
                            type="button"
                            wire:click="loadMoreImages"
                            wire:loading.attr="disabled"
                            wire:target="loadMoreImages"
                        >
                            Load more
                        </button>
                    </div>
                @endif
            @endif
        @endif
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>
