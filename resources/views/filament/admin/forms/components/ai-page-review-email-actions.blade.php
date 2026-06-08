@php
    $emailAction = 'callMountedAction('.\Illuminate\Support\Js::from($emailArguments).')';
@endphp

<div class="twyxtco-ai-page-review-email-actions">
    <x-filament::icon-button
        type="button"
        color="info"
        :icon="\Filament\Support\Icons\Heroicon::OutlinedEnvelope"
        label="Email Results"
        tooltip="Email Results"
        size="xl"
        :loading-indicator="false"
        class="twyxtco-ai-page-review-action-btn"
        wire:click="{{ $emailAction }}"
        wire:loading.attr="disabled"
        wire:target="callMountedAction"
    />
</div>
