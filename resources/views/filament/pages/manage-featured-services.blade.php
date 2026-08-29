<x-filament-panels::page>
    <div class="space-y-4">
        @if ($needsMigration)
            <section class="fi-section rounded-xl bg-white p-6 shadow-sm ring-1 ring-danger-600/20 dark:bg-gray-900 dark:ring-danger-500/30">
                <h2 class="text-base font-semibold text-danger-600 dark:text-danger-400">Database update required</h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    The storefront defaults feature needs a migration that is not on this server yet.
                    SSH into the server and run:
                </p>
                <pre class="mt-3 overflow-x-auto rounded-lg bg-gray-950 px-4 py-3 text-sm text-gray-100">cd /home/vxxapwzq/boostdz.com
php artisan migrate --force</pre>
            </section>
        @else
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
                            $this->platformSlug === $platform->slug
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
        @endif
    </div>
</x-filament-panels::page>
