<x-filament-panels::page>
    <div class="space-y-6">
        <p class="text-sm text-gray-500 dark:text-gray-400">
            Control the landing page reviews carousel, stat cards, and the leave-a-review call to action.
            Drag rows to reorder which reviews appear first.
        </p>

        <section class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            {{ $this->content }}
        </section>

        <section>
            {{ $this->table }}
        </section>
    </div>
</x-filament-panels::page>
