@php
    use Illuminate\Support\Str;

    $fieldWrapperView = $getFieldWrapperView();
    $id = $getId();
    $images = $getImages();
    $statePath = $getStatePath();
    $wireModelAttribute = $applyStateBindingModifiers('wire:model');
@endphp

<x-dynamic-component :component="$fieldWrapperView" :field="$field">
    <style>
        .gfree-image-picker {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
            gap: 0.75rem;
            max-height: min(72vh, 760px);
            overflow: auto;
            padding-right: 0.25rem;
        }

        .gfree-image-picker-option {
            display: block;
            cursor: pointer;
        }

        .gfree-image-picker-input {
            position: absolute;
            width: 1px;
            height: 1px;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .gfree-image-picker-card {
            display: block;
            border: 1px solid rgb(209 213 219);
            border-radius: 0.5rem;
            background: white;
            overflow: hidden;
            transition: border-color 120ms ease, box-shadow 120ms ease;
        }

        .dark .gfree-image-picker-card {
            border-color: rgb(55 65 81);
            background: rgb(17 24 39);
        }

        .gfree-image-picker-input:checked + .gfree-image-picker-card {
            border-color: rgb(217 119 6);
            box-shadow: 0 0 0 3px rgb(217 119 6 / 0.35);
        }

        .gfree-image-picker-card__image {
            display: block;
            width: 100%;
            height: 4.75rem;
            object-fit: contain;
            background: rgb(243 244 246);
        }

        .dark .gfree-image-picker-card__image {
            background: rgb(3 7 18);
        }

        .gfree-image-picker-card__body {
            display: block;
            padding: 0.5rem;
        }

        .gfree-image-picker-card__title,
        .gfree-image-picker-card__path,
        .gfree-image-picker-card__meta {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .gfree-image-picker-card__title {
            color: rgb(17 24 39);
            font-size: 0.75rem;
            font-weight: 700;
        }

        .dark .gfree-image-picker-card__title {
            color: white;
        }

        .gfree-image-picker-card__path,
        .gfree-image-picker-card__meta {
            color: rgb(107 114 128);
            font-size: 0.6875rem;
        }

        @media (min-width: 1280px) {
            .gfree-image-picker {
                grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
            }
        }

        @media (max-width: 640px) {
            .gfree-image-picker {
                grid-template-columns: repeat(auto-fill, minmax(145px, 1fr));
            }
        }
    </style>

    @if ($images->isEmpty())
        <div class="rounded-lg border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
            No uploaded images were found.
        </div>
    @else
        <div class="gfree-image-picker">
            @foreach ($images as $image)
                @php
                    $optionId = $id.'-'.Str::slug($image['path']).'-'.$loop->index;
                @endphp

                <label for="{{ $optionId }}" class="gfree-image-picker-option">
                    <input
                        id="{{ $optionId }}"
                        type="radio"
                        name="{{ $id }}"
                        value="{{ $image['path'] }}"
                        {{ $wireModelAttribute }}="{{ $statePath }}"
                        class="gfree-image-picker-input"
                    >

                    <span class="gfree-image-picker-card">
                        <img
                            src="{{ $image['url'] }}"
                            alt=""
                            class="gfree-image-picker-card__image"
                            loading="lazy"
                        >

                        <span class="gfree-image-picker-card__body">
                            <span class="gfree-image-picker-card__title" title="{{ $image['name'] }}">
                                {{ $image['name'] }}
                            </span>
                            <span class="gfree-image-picker-card__path" title="{{ $image['path'] }}">
                                {{ $image['path'] }}
                            </span>
                            <span class="gfree-image-picker-card__meta">
                                {{ collect([$image['dimensions_for_humans'] ?? null, $image['size_for_humans'] ?? null])->filter()->implode(' | ') }}
                            </span>
                            <span @class([
                                'gfree-image-picker-card__meta',
                            ])>
                                {{ $image['usage_summary'] ?? 'Unused' }}
                            </span>
                        </span>
                    </span>
                </label>
            @endforeach
        </div>
    @endif
</x-dynamic-component>
