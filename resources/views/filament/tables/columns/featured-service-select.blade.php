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
    wire:ignore.self
    x-data="{
        open: false,
        search: '',
        categoryId: {{ $record?->id }},
        selected: @js($selectedKey),
        selectedLabel: @js($selectedLabel),
        options: @js($optionsList),
        get filtered() {
            const q = this.search.trim().toLowerCase();
            if (!q) {
                return this.options;
            }
            return this.options.filter((option) => option.label.toLowerCase().includes(q));
        },
        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.$nextTick(() => this.$refs.search?.focus());
            } else {
                this.search = '';
            }
        },
        close() {
            this.open = false;
            this.search = '';
        },
        pick(value, label) {
            this.selected = value;
            this.selectedLabel = label || '— None —';
            this.close();
            $wire.updateFeaturedService(this.categoryId, value);
        },
    }"
    @keydown.escape.window="close()"
    class="relative min-w-[18rem] max-w-full"
>
    <button
        type="button"
        @click="toggle()"
        class="fi-select-input flex w-full items-center justify-between gap-2 rounded-lg border border-gray-300 bg-white px-2.5 py-1.5 text-left text-sm text-gray-950 shadow-sm hover:border-gray-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:border-gray-600 dark:bg-gray-900 dark:text-white dark:hover:border-gray-500"
    >
        <span class="truncate" x-text="selectedLabel"></span>
        <svg class="size-4 shrink-0 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" />
        </svg>
    </button>

    <div
        x-show="open"
        x-transition
        @click.outside="close()"
        class="absolute z-50 mt-1 w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg dark:border-gray-700 dark:bg-gray-900"
        style="display: none;"
    >
        <div class="border-b border-gray-200 p-2 dark:border-gray-700">
            <input
                x-ref="search"
                type="search"
                x-model="search"
                placeholder="Search services…"
                class="fi-input block w-full rounded-md border border-gray-300 bg-white px-2.5 py-1.5 text-sm text-gray-950 placeholder:text-gray-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/30 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                @keydown.enter.prevent=""
            />
        </div>

        <ul class="max-h-56 overflow-y-auto py-1 text-sm">
            <li>
                <button
                    type="button"
                    @click="pick('', '— None —')"
                    class="flex w-full px-3 py-2 text-left text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-gray-800"
                    :class="{ 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300': selected === '' }"
                >
                    — None —
                </button>
            </li>
            <template x-for="option in filtered" :key="option.value">
                <li>
                    <button
                        type="button"
                        @click="pick(option.value, option.label)"
                        class="flex w-full px-3 py-2 text-left hover:bg-gray-50 dark:hover:bg-gray-800"
                        :class="{ 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300': selected === option.value }"
                        x-text="option.label"
                    ></button>
                </li>
            </template>
            <li x-show="filtered.length === 0" class="px-3 py-2 text-gray-500 dark:text-gray-400">
                No services match your search.
            </li>
        </ul>
    </div>
</div>
