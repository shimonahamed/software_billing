<?php

namespace SoftwareBilling;

use Illuminate\Support\ServiceProvider;

class SoftwareBillingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(
            __DIR__ . '/../database/migrations'
        );

        $this->publishes([
            __DIR__ . '/../config/software-billing.php'
            => config_path('software-billing.php'),
        ], 'software-billing-config');
    }
}