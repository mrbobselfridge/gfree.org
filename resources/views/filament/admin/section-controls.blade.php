<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        class="flex h-full min-h-10 w-full items-center justify-end gap-2 text-lg font-bold"
        style="padding-top:32px; align-items: center; display: flex; font-size: 1rem; font-weight: 800; height: 100%; justify-content: flex-end; line-height: 1.5rem; min-height: 2.5rem; text-align: right; width: 100%;"
        x-data="{ sectionIds: @js($sectionIds) }"
    >
        <button
            type="button"
            class="font-bold text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
            style="font-size: inherit; font-weight: inherit; line-height: inherit; padding-right:10px;"
            x-on:click="sectionIds.forEach((id) => $dispatch('collapse-section', { id }))"
        >
            Collapse all
        </button>

        <span class="font-bold text-gray-400 dark:text-gray-500">|</span>

        <button
            type="button"
            class="font-bold text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
            style="font-size: inherit; font-weight: inherit; line-height: inherit;padding-left:10px;padding-right:5px;"
            x-on:click="sectionIds.forEach((id) => $dispatch('expand-section', { id }))"
        >
            Expand all
        </button>
    </div>
</x-dynamic-component>
