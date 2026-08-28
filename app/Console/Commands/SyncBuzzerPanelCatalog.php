<?php

namespace App\Console\Commands;

use App\Models\Provider;
use App\Services\Catalog\CatalogSyncService;
use Illuminate\Console\Command;

class SyncBuzzerPanelCatalog extends Command
{
    protected $signature = 'buzzerpanel:sync-catalog {provider? : Provider slug}';

    protected $description = 'Sync BuzzerPanel service catalog and local pricing';

    public function handle(CatalogSyncService $catalogSync): int
    {
        $slug = $this->argument('provider') ?: config('buzzerpanel.provider_slug');
        $provider = Provider::query()->where('slug', $slug)->first();

        if (! $provider) {
            $this->error("Provider [{$slug}] not found. Run db:seed --class=ProviderSeeder first.");

            return self::FAILURE;
        }

        $log = $catalogSync->sync($provider);

        if ($log->status === 'success') {
            $this->info($log->message);

            return self::SUCCESS;
        }

        $this->error($log->message);

        return self::FAILURE;
    }
}
