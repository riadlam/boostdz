<x-filament-panels::page>
    <div class="space-y-4">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Pick the default service shown to users on the dashboard preset cards and pricing page for each category.
            Only categories with a healthy default appear on the storefront.
        </p>

        <section class="fi-section rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Platform</p>
            <div class="flex flex-wrap gap-2">
                @foreach ($this->platforms as $platform)
                    <button
                        type="button"
                        wire:click="selectPlatform(@js($platform->slug))"
                        @class([
                            'rounded-lg px-3 py-1.5 text-sm font-medium transition',
                            $platformSlug === $platform->slug
                                ? 'bg-primary-600 text-white shadow-sm'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700',
                        ])
                    >
                        {{ $platform->name }}
                    </button>
                @endforeach
            </div>
        </section>

        <section>
            {{ $this->table }}
        </section>
    </div>
</x-filament-panels::page>
