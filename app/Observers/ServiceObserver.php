<?php

namespace App\Observers;

use App\Models\Service;
use App\Services\Catalog\FeaturedServiceHealth;

class ServiceObserver
{
    public function __construct(private readonly FeaturedServiceHealth $featuredHealth) {}

    public function updated(Service $service): void
    {
        if (! $service->wasChanged('is_active')) {
            return;
        }

        if ($service->is_active) {
            return;
        }

        $this->featuredHealth->checkAndNotifyForService($service);
        $this->featuredHealth->clearStorefrontCache();
    }
}
