<?php

namespace SoftwareBilling;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Console\Scheduling\Schedule;

use SoftwareBilling\Console\Commands\UninstallCommand;
use SoftwareBilling\Console\Commands\FetchBillingDataCommand;

class SoftwareBillingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Register package commands and scheduler
        if ($this->app->runningInConsole()) {

            $this->commands([
                UninstallCommand::class,
                FetchBillingDataCommand::class,
            ]);

            // Register automatic billing API scheduler
            $this->app->booted(function () {

                $schedule = $this->app->make(Schedule::class);

                $schedule->command('software-billing:fetch')
                    ->twiceDaily(10, 22)
                    ->withoutOverlapping();
            });
        }

        // Load migrations
        $this->loadMigrationsFrom(
            __DIR__ . '/../database/migrations'
        );

        // Publish configuration
        $this->publishes([
            __DIR__ . '/../config/software-billing.php'
            => config_path('software-billing.php'),
        ], 'software-billing-config');

        // Load routes
        $this->loadRoutesFrom(
            __DIR__ . '/../routes/web.php'
        );

        // Load views
        $this->loadViewsFrom(
            __DIR__ . '/../resources/views',
            'software-billing'
        );

        // Publish Vue component
        $this->publishes([
            __DIR__ . '/../resources/js/SoftwareBilling.vue'
            => resource_path('js/vendor/software-billing/SoftwareBilling.vue'),
        ], 'software-billing-assets');

        // Blade directive
        Blade::directive('softwareBilling', function ($expression) {

            $expression = trim((string) $expression);

            $label = $expression !== ''
                ? $expression
                : "'Billing Configuration'";

            return "<?php echo '<meta name=\"csrf-token\" content=\"' . csrf_token() . '\">'; ?>
                <?php echo view('software-billing::button', [
                    'label' => {$label},
                ])->render(); ?>";
        });
    }
}
