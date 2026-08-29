<?php

namespace App\Console\Commands;

use App\Services\Orders\OrderService;
use Illuminate\Console\Command;

class SyncOpenOrdersStatus extends Command
{
    protected $signature = 'orders:sync-open-status {--limit=25 : Max orders to poll}';

    protected $description = 'Poll BuzzerPanel for open order delivery status (start_count / remains)';

    public function handle(OrderService $orderService): int
    {
        ini_set('memory_limit', '256M');

        $synced = $orderService->syncOpenOrders((int) $this->option('limit'));

        foreach ($synced as $order) {
            $delivery = $order->delivery();
            $this->line(sprintf(
                'Order #%d → %s · %s (%.1f%%)',
                $order->id,
                $order->status->value,
                $delivery->label ?? 'n/a',
                $delivery->percent,
            ));
        }

        $this->info("Synced {$synced->count()} orders.");

        return self::SUCCESS;
    }
}
