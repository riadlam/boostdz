<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Database\Eloquent\Relations\Relation::enforceMorphMap([
            'user' => \App\Models\User::class,
            'deposit' => \App\Models\Deposit::class,
            'order' => \App\Models\Order::class,
        ]);
    }
}
