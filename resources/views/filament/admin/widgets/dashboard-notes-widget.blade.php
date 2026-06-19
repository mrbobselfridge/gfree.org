<x-filament-widgets::widget
    class="twyxtco-dashboard-widget twyxtco-dashboard-notes-widget"
    data-twyxtco-dashboard-widget="{{ $widgetKey }}"
>
    <x-filament::section>
        <div class="twyxtco-dashboard-widget-shell">
            <div class="twyxtco-dashboard-widget-controls">
                @if (filled($actionLabel) && filled($actionUrl))
                    <a
                        href="{{ $actionUrl }}"
                        class="twyxtco-dashboard-widget-action"
                        wire:navigate
                    >
                        {{ $actionLabel }}
                    </a>
                @endif

                <button
                    type="button"
                    class="twyxtco-dashboard-widget-drag-handle"
                    title="Move {{ $heading }}"
                    aria-label="Move {{ $heading }}"
                    data-twyxtco-dashboard-widget-drag-handle
                >
                    <svg
                        class="twyxtco-dashboard-widget-control-icon"
                        aria-hidden="true"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke-width="1.8"
                        stroke="currentColor"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18m0-18 3 3m-3-3-3 3m3 15 3-3m-3 3-3-3M3 12h18m-18 0 3-3m-3 3 3 3m15-3-3-3m3 3-3 3" />
                    </svg>
                </button>
            </div>

            <div class="twyxtco-dashboard-widget-header">
                <h2 class="twyxtco-dashboard-widget-title">
                    {{ $heading }}
                </h2>

                <p
                    class="twyxtco-dashboard-widget-description"
                    data-twyxtco-dashboard-widget-description
                >
                    {{ $description }}
                </p>
            </div>
        </div>

        <div class="twyxtco-dashboard-widget-body" data-twyxtco-dashboard-widget-body>
            <div class="twyxtco-dashboard-notes-content">
                {!! $notesHtml !!}
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
