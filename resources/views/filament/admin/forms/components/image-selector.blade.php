@php
    $fieldWrapperView = $getFieldWrapperView();
@endphp

<x-dynamic-component :component="$fieldWrapperView" :field="$field">
    <style>
        .twyxtco-image-selector {
            position: relative;
        }

        .twyxtco-image-selector__panel {
            position: relative;
            min-height: 8rem;
            height: clamp(8rem, 16vw, 10rem);
            overflow: hidden;
            border: 1px solid rgb(209 213 219);
            border-radius: 0.5rem;
            background: rgb(249 250 251);
            perspective: 1200px;
        }

        .dark .twyxtco-image-selector__panel {
            border-color: rgb(75 85 99);
            background: rgb(17 24 39);
        }

        .twyxtco-image-selector__actions {
            position: absolute;
            top: 0.8125rem;
            left: 50%;
            z-index: 5;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.2rem;
            transform: translateX(-50%);
        }

        .twyxtco-image-selector__actions .twyxtco-image-selector__icon-action.fi-icon-btn {
            flex: 0 0 auto;
            width: 1.85rem !important;
            height: 1.85rem !important;
            min-width: 1.85rem;
            min-height: 1.85rem;
            margin: 0 !important;
        }

        .twyxtco-image-selector__actions .fi-icon-btn {
            min-width: 1.85rem;
            min-height: 1.85rem;
            width: 1.85rem;
            height: 1.85rem;
            border-radius: 999px;
            background: color-mix(in srgb, white 88%, transparent);
            box-shadow: 0 0.25rem 0.75rem rgb(15 23 42 / 0.18);
            backdrop-filter: blur(8px);
        }

        .dark .twyxtco-image-selector__actions .fi-icon-btn {
            background: color-mix(in srgb, rgb(17 24 39) 82%, transparent);
            box-shadow: 0 0.25rem 0.75rem rgb(0 0 0 / 0.32);
        }

        .twyxtco-image-selector__actions .fi-icon-btn svg {
            width: 0.75rem;
            height: 0.75rem;
        }

        .twyxtco-image-selector__actions .twyxtco-image-selector__icon-action.fi-icon-btn > .fi-icon {
            width: 0.75rem !important;
            height: 0.75rem !important;
        }

        .twyxtco-image-selector__flip {
            position: relative;
            width: 100%;
            height: 100%;
            transform-style: preserve-3d;
            transition: transform 180ms ease;
        }

        .twyxtco-image-selector__flip.is-flipped {
            transform: rotateY(180deg);
        }

        .twyxtco-image-selector__front,
        .twyxtco-image-selector__back {
            position: absolute;
            inset: 0;
            overflow: hidden;
            border: 0;
            padding: 0;
            background: transparent;
            color: inherit;
            font: inherit;
            text-align: left;
            backface-visibility: hidden;
        }

        .twyxtco-image-selector__front {
            cursor: pointer;
        }

        .twyxtco-image-selector__back {
            display: flex;
            flex-direction: column;
            justify-content: center;
            transform: rotateY(180deg);
            cursor: pointer;
        }

        .twyxtco-image-selector__image {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: rgb(243 244 246);
        }

        .dark .twyxtco-image-selector__image {
            background: rgb(3 7 18);
        }

        .twyxtco-image-selector__details {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 0.35rem;
            height: 100%;
            padding: 1rem;
        }

        .twyxtco-image-selector__title {
            overflow: hidden;
            color: rgb(17 24 39);
            font-size: 0.8125rem;
            font-weight: 700;
            line-height: 1.2;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .dark .twyxtco-image-selector__title {
            color: white;
        }

        .twyxtco-image-selector__path,
        .twyxtco-image-selector__missing,
        .twyxtco-image-selector__empty {
            overflow-wrap: anywhere;
            color: rgb(107 114 128);
            font-size: 0.75rem;
            line-height: 1.25;
        }

        .dark .twyxtco-image-selector__path,
        .dark .twyxtco-image-selector__missing,
        .dark .twyxtco-image-selector__empty {
            color: rgb(156 163 175);
        }

        .twyxtco-image-selector__empty {
            display: flex;
            width: 100%;
            height: 100%;
            align-items: center;
            justify-content: center;
            padding: 2.75rem 1rem 1rem;
            text-align: center;
        }

        .twyxtco-image-selector__tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem;
            padding-top: 0.25rem;
        }

        .twyxtco-image-selector__tag {
            border-radius: 999px;
            background: rgb(245 158 11 / 0.16);
            padding: 0.125rem 0.375rem;
            color: rgb(146 64 14);
            font-size: 0.6875rem;
            font-weight: 700;
        }

        .dark .twyxtco-image-selector__tag {
            color: rgb(251 191 36);
        }
    </style>

    <div class="twyxtco-image-selector" x-data="{ flipped: false }">
        <div class="twyxtco-image-selector__panel">
            <div class="twyxtco-image-selector__actions" aria-label="Image actions" x-show="! flipped" x-on:click.stop>
                {{ $getAction('chooseExistingImage') }}
                {{ $getAction('openImage') }}
                {{ $getAction('detachImage') }}
                {{ $getAction('addImage') }}
                {{ $getAction('editImage') }}
            </div>

            @if (filled($selectedPath))
                <div class="twyxtco-image-selector__flip" x-bind:class="{ 'is-flipped': flipped }">
                    <button
                        type="button"
                        class="twyxtco-image-selector__front"
                        title="Click to view details"
                        aria-label="Click to view details"
                        x-on:click="flipped = true"
                    >
                        @if ($selectedImageExists && filled($selectedImageUrl))
                            <img
                                src="{{ $selectedImageUrl }}"
                                alt=""
                                class="twyxtco-image-selector__image"
                            >
                        @else
                            <div class="twyxtco-image-selector__empty">
                                Image file not found on disk.
                            </div>
                        @endif
                    </button>

                    <button
                        type="button"
                        class="twyxtco-image-selector__back"
                        title="Click to view image"
                        aria-label="Click to view image"
                        x-on:click="flipped = false"
                    >
                        <span class="twyxtco-image-selector__details">
                            <span class="twyxtco-image-selector__title" title="{{ $selectedImageTitle ?: basename($selectedPath) }}">
                                {{ $selectedImageTitle ?: basename($selectedPath) }}
                            </span>
                            <span class="twyxtco-image-selector__path" title="{{ $selectedPath }}">
                                {{ $selectedPath }}
                            </span>

                            @if ($selectedImageTags)
                                <span class="twyxtco-image-selector__tags">
                                    @foreach ($selectedImageTags as $tag)
                                        <span class="twyxtco-image-selector__tag">{{ $tag }}</span>
                                    @endforeach
                                </span>
                            @endif
                        </span>
                    </button>
                </div>
            @else
                <div class="twyxtco-image-selector__empty">
                    No image selected
                </div>
            @endif
        </div>
    </div>
</x-dynamic-component>
