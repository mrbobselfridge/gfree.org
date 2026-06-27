@php
    use Illuminate\Support\Str;

    $fieldWrapperView = $getFieldWrapperView();
    $id = $getId();
    $statePath = $getStatePath();
    $selectedPhotoId = $getState();
    $results = $getSearchResults();
    $photos = collect($results['results']);
@endphp

<x-dynamic-component :component="$fieldWrapperView" :field="$field">
    <style>
        .twyxtco-unsplash-picker-submit {
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

        .twyxtco-unsplash-picker-submit:hover {
            background: rgb(180 83 9);
        }

        .twyxtco-unsplash-picker-submit:disabled {
            cursor: not-allowed;
            opacity: 0.55;
        }

        .twyxtco-unsplash-picker-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .twyxtco-unsplash-picker-summary {
            color: rgb(107 114 128);
            font-size: 0.8125rem;
        }

        .dark .twyxtco-unsplash-picker-summary {
            color: rgb(156 163 175);
        }

        .twyxtco-unsplash-picker-shell {
            display: flex;
            max-height: min(58vh, 680px);
            min-height: 0;
            flex-direction: column;
        }

        .twyxtco-unsplash-picker-results {
            flex: 1 1 auto;
            min-height: 0;
            overflow: auto;
            padding-right: 0.25rem;
        }

        .twyxtco-unsplash-picker-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
            gap: 0.75rem;
        }

        .twyxtco-unsplash-picker-option {
            display: block;
            cursor: pointer;
        }

        .twyxtco-unsplash-picker-input {
            position: absolute;
            width: 1px;
            height: 1px;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .twyxtco-unsplash-picker-card {
            display: block;
            overflow: hidden;
            border: 1px solid rgb(209 213 219);
            border-radius: 0.5rem;
            background: white;
            transition: border-color 120ms ease, box-shadow 120ms ease;
        }

        .dark .twyxtco-unsplash-picker-card {
            border-color: rgb(55 65 81);
            background: rgb(17 24 39);
        }

        .twyxtco-unsplash-picker-input:checked + .twyxtco-unsplash-picker-card {
            border-color: rgb(217 119 6);
            box-shadow: 0 0 0 3px rgb(217 119 6 / 0.35);
        }

        .twyxtco-unsplash-picker-card__image {
            display: block;
            width: 100%;
            height: 7rem;
            object-fit: cover;
            background: rgb(243 244 246);
        }

        .dark .twyxtco-unsplash-picker-card__image {
            background: rgb(3 7 18);
        }

        .twyxtco-unsplash-picker-card__body {
            display: block;
            padding: 0.5rem;
        }

        .twyxtco-unsplash-picker-card__title,
        .twyxtco-unsplash-picker-card__meta {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .twyxtco-unsplash-picker-card__title {
            color: rgb(17 24 39);
            font-size: 0.75rem;
            font-weight: 700;
        }

        .dark .twyxtco-unsplash-picker-card__title {
            color: white;
        }

        .twyxtco-unsplash-picker-card__meta {
            color: rgb(107 114 128);
            font-size: 0.6875rem;
        }

        @media (max-width: 640px) {
            .twyxtco-unsplash-picker-grid {
                grid-template-columns: repeat(auto-fill, minmax(145px, 1fr));
            }
        }
    </style>

    <div
        x-data="{
            selectedPhotoId: @js(filled($selectedPhotoId) ? (string) $selectedPhotoId : null),
            submitting: false,
            async selectPhoto(photoId, submit = false) {
                this.selectedPhotoId = photoId;
                await $wire.set(@js($statePath), photoId, false);

                if (submit) {
                    await this.useSelectedPhoto();
                }
            },
            async useSelectedPhoto() {
                if (! this.selectedPhotoId || this.submitting) {
                    return;
                }

                this.submitting = true;

                try {
                    await $wire.set(@js($statePath), this.selectedPhotoId);
                    await $wire.callMountedAction();
                } finally {
                    this.submitting = false;
                }
            },
        }"
    >
        <div class="twyxtco-unsplash-picker-shell">
            <div class="twyxtco-unsplash-picker-header">
                <p class="twyxtco-unsplash-picker-summary">
                    @if (! $results['configured'])
                        Add UNSPLASH_ACCESS_KEY to enable Unsplash search.
                    @elseif ($results['error'])
                        {{ $results['error'] }}
                    @elseif (blank($results['query']))
                        Search Unsplash to find licensed replacement images.
                    @else
                        {{ $photos->count() }} of {{ $results['total'] }} {{ \Illuminate\Support\Str::plural('photo', $results['total']) }} found
                    @endif
                </p>
                <button
                    type="button"
                    class="twyxtco-unsplash-picker-submit"
                    x-on:click.prevent.stop="useSelectedPhoto()"
                    x-bind:disabled="! selectedPhotoId || submitting"
                >
                    Import selected photo
                </button>
            </div>

            <div class="twyxtco-unsplash-picker-results">
                @if ($photos->isEmpty())
                    <div class="rounded-lg border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                        @if (! $results['configured'])
                            Unsplash is not configured yet.
                        @elseif (blank($results['query']))
                            Enter a search term above.
                        @elseif ($results['error'])
                            {{ $results['error'] }}
                        @else
                            No Unsplash photos match this search.
                        @endif
                    </div>
                @else
                    <div class="twyxtco-unsplash-picker-grid">
                        @foreach ($photos as $photo)
                            @php
                                $photoId = (string) ($photo['id'] ?? '');
                                $optionId = $id.'-'.Str::slug($photoId).'-'.$loop->index;
                                $title = $photo['description'] ?: ($photo['alt_description'] ?: 'Unsplash photo');
                                $dimensions = filled($photo['width'] ?? null) && filled($photo['height'] ?? null)
                                    ? "{$photo['width']} x {$photo['height']}"
                                    : null;
                            @endphp

                            <label
                                for="{{ $optionId }}"
                                class="twyxtco-unsplash-picker-option"
                                x-on:dblclick.prevent="selectPhoto(@js($photoId), true)"
                            >
                                <input
                                    id="{{ $optionId }}"
                                    type="radio"
                                    name="{{ $id }}"
                                    value="{{ $photoId }}"
                                    x-bind:checked="selectedPhotoId === @js($photoId)"
                                    x-on:change="selectPhoto($event.target.value)"
                                    class="twyxtco-unsplash-picker-input"
                                >

                                <span class="twyxtco-unsplash-picker-card">
                                    <img
                                        src="{{ $photo['thumb_url'] ?? $photo['preview_url'] }}"
                                        alt=""
                                        class="twyxtco-unsplash-picker-card__image"
                                        loading="lazy"
                                    >

                                    <span class="twyxtco-unsplash-picker-card__body">
                                        <span class="twyxtco-unsplash-picker-card__title" title="{{ $title }}">
                                            {{ $title }}
                                        </span>
                                        <span class="twyxtco-unsplash-picker-card__meta" title="{{ $photo['author_name'] ?? 'Unknown photographer' }}">
                                            Photo by {{ $photo['author_name'] ?? 'Unknown photographer' }}
                                        </span>
                                        <span class="twyxtco-unsplash-picker-card__meta">
                                            {{ $dimensions }}
                                        </span>
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-dynamic-component>
