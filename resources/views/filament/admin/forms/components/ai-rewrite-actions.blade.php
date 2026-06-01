<div class="flex flex-wrap items-center gap-3">
    <x-filament::button
        type="button"
        color="primary"
        wire:click="callMountedAction"
        wire:loading.attr="disabled"
        wire:target="callMountedAction"
    >
        <span wire:loading.remove wire:target="callMountedAction">AI Rewrite</span>
        <span wire:loading wire:target="callMountedAction">Working...</span>
    </x-filament::button>

    <x-filament::button
        type="button"
        color="success"
        wire:click="callMountedAction(@js($acceptArguments))"
        wire:loading.attr="disabled"
        wire:target="callMountedAction"
    >
        Accept Changes
    </x-filament::button>

    <x-filament::button
        type="button"
        color="danger"
        wire:click="unmountAction"
    >
        Cancel
    </x-filament::button>
</div>
