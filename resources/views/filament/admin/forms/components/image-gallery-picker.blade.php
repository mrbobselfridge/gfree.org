@php
    use Illuminate\Support\Str;

    $fieldWrapperView = $getFieldWrapperView();
    $id = $getId();
    $imageResults = $getImageResults();
    $images = $imageResults['items'];
    $totalImages = $imageResults['total'];
    $filteredImages = $imageResults['filtered_total'];
    $hasMoreImages = $imageResults['has_more'];
    $limitStatePath = $getLimitStatePath();
    $nextLimit = $getNextLimit();
    $statePath = $getStatePath();
    $wireModelAttribute = $applyStateBindingModifiers('wire:model');
@endphp

<x-dynamic-component :component="$fieldWrapperView" :field="$field">
    <style>
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

        .twyxtco-image-picker-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .twyxtco-image-picker-summary {
            color: rgb(107 114 128);
            font-size: 0.8125rem;
        }

        .dark .twyxtco-image-picker-summary {
            color: rgb(156 163 175);
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

        .twyxtco-image-picker-load-more {
            display: flex;
            justify-content: center;
            margin-top: 0.75rem;
        }

        @media (min-width: 1280px) {
            .twyxtco-image-picker {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            }
        }

        @media (max-width: 640px) {
            .twyxtco-image-picker {
                grid-template-columns: repeat(auto-fill, minmax(145px, 1fr));
            }
        }
    </style>

    <div x-data>
        <div class="twyxtco-image-picker-header">
            <p class="twyxtco-image-picker-summary">
                {{ $images->count() }} of {{ $filteredImages }} {{ \Illuminate\Support\Str::plural('image', $filteredImages) }} shown
                @if ($filteredImages !== $totalImages)
                    ({{ $totalImages }} total)
                @endif
            </p>
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
                @if ($totalImages === 0)
                    No uploaded images were found.
                @else
                    No images match your search.
                @endif
            </div>
        @else
            <div class="twyxtco-image-picker">
            @foreach ($images as $image)
                @php
                    $optionId = $id.'-'.Str::slug($image['path']).'-'.$loop->index;
                @endphp

                <label
                    for="{{ $optionId }}"
                    class="twyxtco-image-picker-option"
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

            @if ($hasMoreImages)
                <div class="twyxtco-image-picker-load-more">
                    <button
                        type="button"
                        class="twyxtco-image-picker-submit"
                        x-data
                        x-on:click="$wire.set(@js($limitStatePath), @js($nextLimit))"
                    >
                        Load more
                    </button>
                </div>
            @endif
        @endif
    </div>
</x-dynamic-component>
