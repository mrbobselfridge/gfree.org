<div
    class="text-sm"
    style="display: flex; width: 100%; justify-content: flex-end;"
    x-data="{ sectionIds: @js($sectionIds) }"
>
    <button
        type="button"
        class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
        x-on:click="sectionIds.forEach((id) => $dispatch('collapse-section', { id }))"
    >
        Collapse all
    </button>

    <span class="text-gray-400 dark:text-gray-500" style="margin: 0 0.5rem;">|</span>

    <button
        type="button"
        class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300"
        x-on:click="sectionIds.forEach((id) => $dispatch('expand-section', { id }))"
    >
        Expand all
    </button>
</div>
