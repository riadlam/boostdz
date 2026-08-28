<x-filament-panels::page>
    <div class="space-y-4">
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

        @if ($this->categories->isNotEmpty())
            <section class="fi-section rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Category</p>
                <div class="flex flex-wrap gap-2">
                    <button
                        type="button"
                        wire:click="selectCategory(null)"
                        @class([
                            'rounded-lg px-3 py-1.5 text-sm font-medium transition',
                            blank($categorySlug)
                                ? 'bg-primary-600 text-white shadow-sm'
                                : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700',
                        ])
                    >
                        All categories
                    </button>
                    @foreach ($this->categories as $category)
                        <button
                            type="button"
                            wire:click="selectCategory(@js($category->slug))"
                            @class([
                                'inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium transition',
                                $categorySlug === $category->slug
                                    ? 'bg-primary-600 text-white shadow-sm'
                                    : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700',
                            ])
                        >
                            <span>{{ $category->name }}</span>
                            <span @class([
                                'rounded-full px-1.5 py-0.5 text-[10px] font-semibold tabular-nums',
                                $categorySlug === $category->slug
                                    ? 'bg-white/20 text-white'
                                    : 'bg-gray-200 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                            ])>
                                {{ number_format($category->services_count) }}
                            </span>
                        </button>
                    @endforeach
                </div>
            </section>
        @endif

        <section>
            {{ $this->table }}
        </section>
    </div>
</x-filament-panels::page>
