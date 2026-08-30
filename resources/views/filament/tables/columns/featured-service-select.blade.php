@php
    /** @var \App\Models\CatalogCategory|null $record */
    $record = $getRecord();
    $selected = $getState();
    $selectedKey = filled($selected) ? (string) $selected : '';
    $selectedLabel = filled($selectedKey) ? ($options[$selectedKey] ?? '— None —') : '— None —';
@endphp

<details class="featured-service-picker w-full min-w-[16rem] max-w-[24rem]">
    <summary class="fi-input-wrp flex cursor-pointer list-none items-center justify-between gap-2 rounded-lg bg-white px-3 py-2 text-sm shadow-sm ring-1 ring-gray-950/10 marker:content-none dark:bg-white/5 dark:ring-white/20 [&::-webkit-details-marker]:hidden">
        <span class="truncate text-gray-950 dark:text-white">{{ $selectedLabel }}</span>
        <svg class="size-4 shrink-0 text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" />
        </svg>
    </summary>

    <div class="relative z-10 mt-1 rounded-lg bg-white p-2 shadow-lg ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10">
        <input
            type="search"
            placeholder="Search services…"
            class="block w-full rounded-md border-0 bg-gray-50 px-3 py-2 text-sm text-gray-950 ring-1 ring-gray-950/10 placeholder:text-gray-400 focus:ring-2 focus:ring-primary-600 dark:bg-white/5 dark:text-white dark:ring-white/10"
            oninput="const q = this.value.trim().toLowerCase(); this.closest('.featured-service-picker').querySelectorAll('[data-service-option]').forEach((btn) => { btn.style.display = !q || btn.dataset.search.includes(q) ? '' : 'none'; });"
        />

        <div class="mt-2 max-h-52 space-y-0.5 overflow-y-auto overscroll-contain">
            <button
                type="button"
                wire:click="updateFeaturedService({{ $record?->id }}, '')"
                onclick="this.closest('details').removeAttribute('open')"
                @class([
                    'block w-full rounded-md px-3 py-2 text-left text-sm text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5',
                    blank($selectedKey) ? 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' : '',
                ])
            >
                — None —
            </button>

            @foreach ($options as $value => $label)
                <button
                    type="button"
                    data-service-option
                    data-search="{{ strtolower($label) }}"
                    wire:click="updateFeaturedService({{ $record?->id }}, @js((string) $value))"
                    onclick="this.closest('details').removeAttribute('open')"
                    @class([
                        'block w-full rounded-md px-3 py-2 text-left text-sm text-gray-950 hover:bg-gray-50 dark:text-white dark:hover:bg-white/5',
                        $selectedKey === (string) $value ? 'bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300' : '',
                    ])
                >
                    {{ $label }}
                </button>
            @endforeach
        </div>
    </div>
</details>
