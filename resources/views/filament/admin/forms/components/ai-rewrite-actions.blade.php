<div class="twyxtco-ai-rewrite-actions">
    <x-filament::icon-button
        type="button"
        color="primary"
        :icon="\Filament\Support\Icons\Heroicon::OutlinedSparkles"
        label="AI Rewrite"
        tooltip="AI Rewrite"
        size="xl"
        wire:click="callMountedAction"
        wire:loading.attr="disabled"
        wire:target="callMountedAction"
    />

    <x-filament::icon-button
        type="button"
        color="success"
        :icon="\Filament\Support\Icons\Heroicon::OutlinedCheck"
        label="Accept Changes"
        tooltip="Accept Changes"
        size="xl"
        wire:click="callMountedAction(@js($acceptArguments))"
        wire:loading.attr="disabled"
        wire:target="callMountedAction"
    />

    <x-filament::icon-button
        type="button"
        color="danger"
        :icon="\Filament\Support\Icons\Heroicon::OutlinedXMark"
        label="Cancel"
        tooltip="Cancel"
        size="xl"
        wire:click="unmountAction"
    />

    <div
        class="twyxtco-ai-rewrite-processing"
        wire:loading.flex
        wire:target="callMountedAction"
        role="status"
        aria-live="polite"
    >
        <x-filament::loading-indicator class="twyxtco-ai-rewrite-processing-spinner" />
        <div>
            <div class="twyxtco-ai-rewrite-processing-title">Processing AI rewrite</div>
            <div class="twyxtco-ai-rewrite-processing-message">
                If this times out or returns an error, run the rewrite again.
            </div>
        </div>
    </div>
</div>
