<x-filament-panels::page>
    @php
        $images = $this->getImages();
    @endphp

    <style>
        .gfree-media-summary {
            border: 1px solid rgb(229 231 235);
            border-radius: 0.75rem;
            background: white;
            padding: 1rem;
        }

        .dark .gfree-media-summary {
            border-color: rgb(31 41 55);
            background: rgb(17 24 39);
        }

        .gfree-media-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(210px, 1fr));
            gap: 0.75rem;
        }

        .gfree-media-card {
            overflow: hidden;
            border: 1px solid rgb(229 231 235);
            border-radius: 0.75rem;
            background: white;
        }

        .dark .gfree-media-card {
            border-color: rgb(31 41 55);
            background: rgb(17 24 39);
        }

        .gfree-media-card__image {
            display: block;
            width: 100%;
            aspect-ratio: 4 / 3;
            object-fit: cover;
            background: rgb(243 244 246);
        }

        .dark .gfree-media-card__image {
            background: rgb(3 7 18);
        }

        .gfree-media-card__body {
            padding: 0.75rem;
        }

        .gfree-media-card__title,
        .gfree-media-card__path,
        .gfree-media-card__meta {
            display: block;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .gfree-media-card__title {
            color: rgb(17 24 39);
            font-size: 0.75rem;
            font-weight: 700;
        }

        .dark .gfree-media-card__title {
            color: white;
        }

        .gfree-media-card__path,
        .gfree-media-card__meta {
            color: rgb(107 114 128);
            font-size: 0.6875rem;
        }

        .gfree-media-card__stats {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.5rem;
            margin-top: 0.625rem;
            color: rgb(107 114 128);
            font-size: 0.6875rem;
        }

        .gfree-media-card__stats dt {
            color: rgb(75 85 99);
            font-weight: 650;
        }

        .dark .gfree-media-card__stats dt {
            color: rgb(209 213 219);
        }

        .gfree-media-card__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.625rem;
            font-size: 0.75rem;
        }

        .gfree-media-card__actions a,
        .gfree-media-card__actions button {
            color: rgb(217 119 6);
            font-weight: 650;
        }

        @media (max-width: 640px) {
            .gfree-media-grid {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }
        }
    </style>

    <div class="space-y-6">
        <div class="gfree-media-summary">
            <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">Uploaded images</h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ $images->count() }} {{ \Illuminate\Support\Str::plural('image', $images->count()) }} found in public storage.
                    </p>
                </div>
            </div>
        </div>

        @if ($images->isEmpty())
            <div class="rounded-xl border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
                No uploaded images were found.
            </div>
        @else
            <div class="gfree-media-grid">
                @foreach ($images as $image)
                    <article class="gfree-media-card">
                        <a href="{{ $image['url'] }}" target="_blank" rel="noreferrer">
                            <img
                                src="{{ $image['url'] }}"
                                alt=""
                                class="gfree-media-card__image"
                                loading="lazy"
                            >
                        </a>

                        <div class="gfree-media-card__body">
                            <div class="min-w-0">
                                <h3 class="gfree-media-card__title" title="{{ $image['name'] }}">
                                    {{ $image['name'] }}
                                </h3>
                                <p class="gfree-media-card__path" title="{{ $image['path'] }}">
                                    {{ $image['path'] }}
                                </p>
                            </div>

                            <dl class="gfree-media-card__stats">
                                <div>
                                    <dt class="font-medium text-gray-700 dark:text-gray-300">Size</dt>
                                    <dd>{{ $image['size_for_humans'] }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-700 dark:text-gray-300">Dimensions</dt>
                                    <dd>{{ $image['dimensions_for_humans'] ?? 'Unknown' }}</dd>
                                </div>
                            </dl>

                            <div class="gfree-media-card__actions">
                                <a
                                    href="{{ $image['url'] }}"
                                    target="_blank"
                                    rel="noreferrer"
                                >
                                    Open
                                </a>
                                <a
                                    href="{{ $image['url'] }}"
                                    download="{{ $image['name'] }}"
                                >
                                    Download
                                </a>
                                <button
                                    type="button"
                                    x-data
                                    x-on:click="navigator.clipboard.writeText(@js($image['url']))"
                                >
                                    Copy URL
                                </button>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
