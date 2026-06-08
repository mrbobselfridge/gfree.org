<div class="twyxtco-ai-page-review-actions">
    <x-filament::icon-button
        type="button"
        color="primary"
        :icon="\Filament\Support\Icons\Heroicon::OutlinedSparkles"
        label="AI Review"
        tooltip="AI Review"
        size="xl"
        :loading-indicator="false"
        class="twyxtco-ai-page-review-action-btn"
        wire:click="callMountedAction"
        wire:loading.attr="disabled"
        wire:target="callMountedAction"
    />

    <x-filament::icon-button
        type="button"
        color="danger"
        :icon="\Filament\Support\Icons\Heroicon::OutlinedXMark"
        label="Close"
        tooltip="Close"
        size="xl"
        :loading-indicator="false"
        class="twyxtco-ai-page-review-action-btn"
        wire:click="unmountAction"
    />

    <div
        class="twyxtco-ai-page-review-processing"
        wire:loading.flex
        wire:target="callMountedAction"
        role="status"
        aria-live="polite"
    >
        <x-filament::loading-indicator class="twyxtco-ai-page-review-processing-spinner" />
        <div>
            <div class="twyxtco-ai-page-review-processing-title">Processing AI review</div>
            <div class="twyxtco-ai-page-review-processing-message">
                If this times out or returns an error, run the review again.
            </div>
        </div>
    </div>
</div>
