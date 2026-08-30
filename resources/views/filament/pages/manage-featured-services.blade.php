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

        @push('scripts')
            <script>
                (() => {
                    const register = () => {
                        if (window.__featuredServicePickerRegistered) {
                            return;
                        }
                        window.__featuredServicePickerRegistered = true;

                        Alpine.data('featuredServicePicker', ({ categoryId, selected, selectedLabel, options }) => ({
                        open: false,
                        search: '',
                        categoryId,
                        selected,
                        selectedLabel,
                        options,
                        panelStyle: 'display: none;',
                        pickerId: `featured-service-${categoryId}`,
                        get filtered() {
                            const q = this.search.trim().toLowerCase();
                            if (!q) {
                                return this.options;
                            }
                            return this.options.filter((option) => option.label.toLowerCase().includes(q));
                        },
                        toggle() {
                            if (this.open) {
                                this.close();
                                return;
                            }
                            window.dispatchEvent(new CustomEvent('featured-service-picker-open', {
                                detail: { id: this.pickerId },
                            }));
                            this.positionPanel();
                            this.open = true;
                            this.$nextTick(() => this.$refs.search?.focus());
                        },
                        close() {
                            this.open = false;
                            this.search = '';
                            this.panelStyle = 'display: none;';
                        },
                        positionPanel() {
                            const trigger = this.$refs.trigger;
                            if (!trigger) {
                                return;
                            }
                            const rect = trigger.getBoundingClientRect();
                            const width = Math.max(rect.width, 280);
                            let left = rect.left;
                            if (left + width > window.innerWidth - 12) {
                                left = Math.max(12, window.innerWidth - width - 12);
                            }
                            const top = rect.bottom + 6;
                            this.panelStyle = `top:${top}px;left:${left}px;width:${width}px;`;
                        },
                        pick(value, label) {
                            this.selected = value;
                            this.selectedLabel = label || '— None —';
                            this.close();
                            this.$wire.updateFeaturedService(this.categoryId, value);
                        },
                        init() {
                            this._onScroll = () => {
                                if (this.open) {
                                    this.close();
                                }
                            };
                            this._onResize = () => {
                                if (this.open) {
                                    this.positionPanel();
                                }
                            };
                            window.addEventListener('scroll', this._onScroll, true);
                            window.addEventListener('resize', this._onResize);
                        },
                        destroy() {
                            window.removeEventListener('scroll', this._onScroll, true);
                            window.removeEventListener('resize', this._onResize);
                        },
                    }));
                    };

                    if (window.Alpine) {
                        register();
                    } else {
                        document.addEventListener('alpine:init', register);
                    }
                })();
            </script>
        @endpush
        @endif
    </div>
</x-filament-panels::page>
