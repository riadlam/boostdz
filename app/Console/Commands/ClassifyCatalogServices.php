<?php

namespace App\Console\Commands;

use App\Models\Service;
use App\Services\Catalog\CatalogClassifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ClassifyCatalogServices extends Command
{
    protected $signature = 'catalog:classify {--chunk=500 : Services per chunk}';

    protected $description = 'Classify services into catalog categories, quality tiers, and name facets';

    public function handle(CatalogClassifier $classifier): int
    {
        CatalogClassifier::clearCache();

        $chunk = max(50, (int) $this->option('chunk'));
        $total = Service::query()->count();
        $bar = $this->output->createProgressBar($total);
        $bar->start();
        $now = now();

        Service::query()
            ->with('providerService:id,category')
            ->orderBy('id')
            ->chunkById($chunk, function ($services) use ($classifier, $bar, $now): void {
                foreach ($services as $service) {
                    $payload = $classifier->classifyAttributes($service, $service->providerService?->category);

                    $meta = is_array($service->meta) ? $service->meta : [];
                    $meta['facets'] = $payload['facets_meta'] ?? [];

                    DB::table('services')->where('id', $service->id)->update([
                        'platform' => $payload['platform'],
                        'catalog_category_id' => $payload['catalog_category_id'],
                        'quality_tier' => $payload['quality_tier'],
                        'is_hot' => $payload['is_hot'] ? 1 : 0,
                        'is_cheap' => $payload['is_cheap'] ? 1 : 0,
                        'start_class' => $payload['start_class'],
                        'refill_days' => $payload['refill_days'],
                        'refill_mode' => $payload['refill_mode'],
                        'country_code' => $payload['country_code'],
                        'audience_gender' => $payload['audience_gender'],
                        'refill' => $payload['refill'] ? 1 : 0,
                        'dripfeed' => $payload['dripfeed'] ? 1 : 0,
                        'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE),
                        'updated_at' => $now,
                    ]);

                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
        $this->info("Classified {$total} services.");

        return self::SUCCESS;
    }
}
