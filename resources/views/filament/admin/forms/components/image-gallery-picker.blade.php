@php
    use Illuminate\Support\Str;

    $fieldWrapperView = $getFieldWrapperView();
    $id = $getId();
    $images = $getImages();
    $statePath = $getStatePath();
    $wireModelAttribute = $applyStateBindingModifiers('wire:model');
    $sortOptions = [
        'recent' => 'Most recent',
        'content_type' => 'Content Type',
        'file_name' => 'File Name',
        'size' => 'Size',
        'path' => 'File Path + Name',
        'dimensions' => 'Dimensions',
    ];
    $pickerImages = $images
        ->map(fn (array $image): array => [
            'path' => $image['path'],
            'name' => $image['name'],
            'modified' => $image['modified'] ?? 0,
            'size' => $image['size'] ?? 0,
            'pixels' => is_array($image['dimensions'] ?? null) ? (($image['dimensions'][0] ?? 0) * ($image['dimensions'][1] ?? 0)) : 0,
            'contentType' => data_get($image, 'usage.0.short_label') ?: 'zz-unused',
            'haystack' => collect([
                $image['path'] ?? null,
                $image['name'] ?? null,
                $image['directory'] ?? null,
                $image['usage_summary'] ?? null,
                ...collect($image['usage'] ?? [])
                    ->flatMap(fn (array $usage): array => [
                        $usage['label'] ?? null,
                        $usage['short_label'] ?? null,
                        $usage['detail'] ?? null,
                    ])
                    ->all(),
            ])->filter()->implode(' '),
        ])
        ->values();
@endphp

<x-dynamic-component :component="$fieldWrapperView" :field="$field">
    <style>
        .twyxtco-image-picker-controls {
            display: grid;
            grid-template-columns: minmax(180px, 1fr) minmax(160px, 240px) auto;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            align-items: end;
        }

        .twyxtco-image-picker-control label {
            display: block;
            margin-bottom: 0.25rem;
            color: rgb(75 85 99);
            font-size: 0.75rem;
            font-weight: 650;
        }

        .dark .twyxtco-image-picker-control label {
            color: rgb(209 213 219);
        }

        .twyxtco-image-picker-control input,
        .twyxtco-image-picker-control select {
            width: 100%;
            border: 1px solid rgb(209 213 219);
            border-radius: 0.5rem;
            background: white;
            padding: 0.5rem 0.75rem;
            color: rgb(17 24 39);
            font-size: 0.875rem;
        }

        .dark .twyxtco-image-picker-control input,
        .dark .twyxtco-image-picker-control select {
            border-color: rgb(55 65 81);
            background: rgb(3 7 18);
            color: white;
        }

        .twyxtco-image-picker-submit {
            display: inline-flex;
            min-height: 2.375rem;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            background: rgb(217 119 6);
            padding: 0.5rem 0.875rem;
            color: white;
            font-size: 0.875rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .twyxtco-image-picker-submit:hover {
            background: rgb(180 83 9);
        }

        .twyxtco-image-picker {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
            gap: 0.75rem;
            max-height: min(72vh, 760px);
            overflow: auto;
            padding-right: 0.25rem;
        }

        .twyxtco-image-picker-option {
            display: block;
            cursor: pointer;
        }

        .twyxtco-image-picker-input {
            position: absolute;
            width: 1px;
            height: 1px;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .twyxtco-image-picker-card {
            display: block;
            border: 1px solid rgb(209 213 219);
            border-radius: 0.5rem;
            background: white;
            overflow: hidden;
            transition: border-color 120ms ease, box-shadow 120ms ease;
        }

        .dark .twyxtco-image-picker-card {
            border-color: rgb(55 65 81);
            background: rgb(17 24 39);
        }

        .twyxtco-image-picker-input:checked + .twyxtco-image-picker-card {
            border-color: rgb(217 119 6);
            box-shadow: 0 0 0 3px rgb(217 119 6 / 0.35);
        }

        .twyxtco-image-picker-card__image {
            display: block;
            width: 100%;
            height: 4.75rem;
            object-fit: contain;
            background: rgb(243 244 246);
        }

        .dark .twyxtco-image-picker-card__image {
            background: rgb(3 7 18);
        }

        .twyxtco-image-picker-card__body {
            display: block;
            padding: 0.5rem;
        }

        .twyxtco-image-picker-card__title,
        .twyxtco-image-picker-card__path,
        .twyxtco-image-picker-card__meta {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .twyxtco-image-picker-card__title {
            color: rgb(17 24 39);
            font-size: 0.75rem;
            font-weight: 700;
        }

        .dark .twyxtco-image-picker-card__title {
            color: white;
        }

        .twyxtco-image-picker-card__path,
        .twyxtco-image-picker-card__meta {
            color: rgb(107 114 128);
            font-size: 0.6875rem;
        }

        @media (min-width: 1280px) {
            .twyxtco-image-picker {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            }
        }

        @media (max-width: 640px) {
            .twyxtco-image-picker-controls {
                grid-template-columns: 1fr;
            }

            .twyxtco-image-picker {
                grid-template-columns: repeat(auto-fill, minmax(145px, 1fr));
            }
        }
    </style>

    <div
        x-data="{
            search: '',
            sort: 'recent',
            images: @js($pickerImages),
            normalize(value) {
                return String(value || '').toLowerCase()
            },
            matches(image) {
                return ! this.search || this.normalize(image.haystack).includes(this.normalize(this.search))
            },
            sortedPaths() {
                return [...this.images].sort((a, b) => {
                    if (this.sort === 'content_type') return this.normalize(a.contentType).localeCompare(this.normalize(b.contentType))
                    if (this.sort === 'file_name') return this.normalize(a.name).localeCompare(this.normalize(b.name))
                    if (this.sort === 'size') return Number(b.size || 0) - Number(a.size || 0)
                    if (this.sort === 'path') return this.normalize(a.path).localeCompare(this.normalize(b.path))
                    if (this.sort === 'dimensions') return Number(b.pixels || 0) - Number(a.pixels || 0)

                    return Number(b.modified || 0) - Number(a.modified || 0)
                }).map((image) => image.path)
            },
            order(path) {
                return this.sortedPaths().indexOf(path)
            },
            hasMatches() {
                return this.images.some((image) => this.matches(image))
            },
        }"
    >
        <div class="twyxtco-image-picker-controls">
            <div class="twyxtco-image-picker-control">
                <label for="{{ $id }}-search">Search</label>
                <input
                    id="{{ $id }}-search"
                    type="search"
                    x-model.debounce.200ms="search"
                    placeholder="Search path, filename, or content area"
                >
            </div>

            <div class="twyxtco-image-picker-control">
                <label for="{{ $id }}-sort">Sort by</label>
                <select id="{{ $id }}-sort" x-model="sort">
                    @foreach ($sortOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <button
                type="button"
                class="twyxtco-image-picker-submit"
                wire:click="callMountedAction"
                wire:loading.attr="disabled"
                wire:target="callMountedAction"
            >
                Use selected image
            </button>
        </div>

        @if ($images->isEmpty())
            <div class="rounded-lg border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                No uploaded images were found.
            </div>
        @else
            <div class="twyxtco-image-picker">
            @foreach ($images as $image)
                @php
                    $optionId = $id.'-'.Str::slug($image['path']).'-'.$loop->index;
                    $imageForControls = $pickerImages->firstWhere('path', $image['path']);
                @endphp

                <label
                    for="{{ $optionId }}"
                    class="twyxtco-image-picker-option"
                    x-show="matches(@js($imageForControls))"
                    x-bind:style="{ order: order(@js($image['path'])) }"
                    x-on:dblclick.prevent="$wire.set(@js($statePath), @js($image['path'])); $nextTick(() => $wire.callMountedAction())"
                >
                    <input
                        id="{{ $optionId }}"
                        type="radio"
                        name="{{ $id }}"
                        value="{{ $image['path'] }}"
                        {{ $wireModelAttribute }}="{{ $statePath }}"
                        class="twyxtco-image-picker-input"
                    >

                    <span class="twyxtco-image-picker-card">
                        <img
                            src="{{ $image['url'] }}"
                            alt=""
                            class="twyxtco-image-picker-card__image"
                            loading="lazy"
                        >

                        <span class="twyxtco-image-picker-card__body">
                            <span class="twyxtco-image-picker-card__title" title="{{ $image['name'] }}">
                                {{ $image['name'] }}
                            </span>
                            <span class="twyxtco-image-picker-card__path" title="{{ $image['path'] }}">
                                {{ $image['path'] }}
                            </span>
                            <span class="twyxtco-image-picker-card__meta">
                                {{ collect([$image['dimensions_for_humans'] ?? null, $image['size_for_humans'] ?? null])->filter()->implode(' | ') }}
                            </span>
                            <span @class([
                                'twyxtco-image-picker-card__meta',
                            ])>
                                {{ $image['usage_summary'] ?? 'Unused' }}
                            </span>
                        </span>
                    </span>
                </label>
            @endforeach
            </div>

            <div
                x-cloak
                x-show="! hasMatches()"
                class="rounded-lg border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400"
            >
                No images match your search.
            </div>
        @endif
    </div>
</x-dynamic-component>
