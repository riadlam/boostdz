<?php

namespace App\Console\Commands;

use App\Models\Service;
use App\Services\Pricing\PricingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RefreshPricingSellRates extends Command
{
    protected $signature = 'pricing:refresh-sell-rates {--chunk=500 : Rows per batch}';

    protected $description = 'Recalculate services.sell_rate_dzd from rate_idr using current PRICING_* env rates';

    public function handle(PricingService $pricing): int
    {
        $markup = $pricing->markupPercent();
        $updated = 0;
        $chunk = max(50, (int) $this->option('chunk'));

        Service::query()
            ->select(['id', 'rate_idr'])
            ->orderBy('id')
            ->chunkById($chunk, function ($services) use ($pricing, $markup, &$updated): void {
                DB::transaction(function () use ($services, $pricing, $markup, &$updated): void {
                    foreach ($services as $service) {
                        DB::table('services')
                            ->where('id', $service->id)
                            ->update([
                                'sell_rate_dzd' => $pricing->sellRateDzdPerThousand($service->rate_idr),
                                'markup_percent' => number_format($markup, 2, '.', ''),
                                'updated_at' => now(),
                            ]);
                        $updated++;
                    }
                });
            });

        $this->info("Updated {$updated} services (EUR_IDR={$pricing->eurIdrRate()}, EUR_DZD={$pricing->eurDzdRate()}, markup={$markup}%).");

        return self::SUCCESS;
    }
}
