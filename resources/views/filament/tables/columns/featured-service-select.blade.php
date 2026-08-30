@php
    /** @var \App\Models\CatalogCategory|null $record */
    $record = $getRecord();
    $selected = $getState();
    $selectedKey = filled($selected) ? (string) $selected : '';
    $selectedLabel = filled($selectedKey) ? ($options[$selectedKey] ?? '— None —') : '— None —';
    $optionsList = collect($options)
        ->map(fn (string $label, string|int $value): array => [
            'value' => (string) $value,
            'label' => $label,
        ])
        ->values()
        ->all();
@endphp

<div
    wire:key="featured-service-{{ $record?->id }}"
    x-data="featuredServicePicker({
        categoryId: {{ $record?->id }},
        selected: @js($selectedKey),
        selectedLabel: @js($selectedLabel),
        options: @js($optionsList),
    })"
    x-on:featured-service-picker-open.window="if ($event.detail.id !== pickerId) close()"
    class="w-full min-w-[14rem] max-w-[22rem]"
>
    <button
        x-ref="trigger"
        type="button"
        x-on:click="toggle()"
        class="fi-input-wrp flex w-full items-center justify-between gap-2 rounded-lg bg-white px-3 py-2 text-left text-sm shadow-sm ring-1 ring-gray-950/10 dark:bg-white/5 dark:ring-white/20"
    >
        <span class="truncate text-gray-950 dark:text-white" x-text="selectedLabel"></span>
        <svg class="size-4 shrink-0 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" />
        </svg>
    </button>

    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            x-on:click.outside="close()"
            x-on:keydown.escape.window="close()"
            x-bind:style="panelStyle"
            class="fixed z-[120] overflow-hidden rounded-lg bg-white shadow-xl ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10"
        >
            <div class="border-b border-gray-200 p-2 dark:border-white/10">
                <input
                    x-ref="search"
                    type="search"
                    x-model="search"
                    placeholder="Search services…"
                    class="block w-full rounded-md border-0 bg-gray-50 px-3 py-2 text-sm text-gray-950 ring-1 ring-gray-950/10 placeholder:text-gray-400 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/10 dark:placeholder:text-gray-500"
                    x-on:keydown.enter.prevent=""
                />
            </div>

            <ul class="max-h-60 overflow-y-auto py-1 text-sm">
                <li>
                    <button
                        type="button"
                        x-on:click="pick('', '— None —')"
                        class="block w-full px-3 py-2 text-left text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5"
                        x-bind:class="{ 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300': selected === '' }"
                    >
                        — None —
                    </button>
                </li>
                <template x-for="option in filtered" x-bind:key="option.value">
                    <li>
                        <button
                            type="button"
                            x-on:click="pick(option.value, option.label)"
                            class="block w-full px-3 py-2 text-left text-gray-950 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5"
                            x-bind:class="{ 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300': selected === option.value }"
                            x-text="option.label"
                        ></button>
                    </li>
                </template>
                <li x-show="filtered.length === 0" class="px-3 py-2 text-gray-500 dark:text-gray-400">
                    No services match your search.
                </li>
            </ul>
        </div>
    </template>
</div>
