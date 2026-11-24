<?php

namespace SoftwareBilling\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class UninstallCommand extends Command
{
    protected $signature = 'software-billing:uninstall
                            {--force : Skip confirmation}';

    protected $description = 'Safely remove Software Billing published files without deleting database tables';

    public function handle(): int
    {
        $files = [
            config_path('software-billing.php'),

            resource_path(
                'js/vendor/software-billing/SoftwareBilling.vue'
            ),
        ];

        $this->info('Software Billing Uninstall');
        $this->newLine();

        $this->warn('The following files will be removed:');

        foreach ($files as $file) {
            $this->line('- ' . $file);
        }

        $this->newLine();

        $this->info('Database tables and records will NOT be deleted.');

        if (!$this->option('force') &&
            !$this->confirm('Are you sure you want to continue?', false)) {
            $this->warn('Uninstall cancelled.');

            return self::SUCCESS;
        }

        foreach ($files as $file) {
            if (File::exists($file) && File::isFile($file)) {
                if (File::delete($file)) {
                    $this->info("Removed: {$file}");
                } else {
                    $this->error("Failed to remove: {$file}");

                    return self::FAILURE;
                }
            } else {
                $this->line("Not found: {$file}");
            }
        }

        // Remove the package directory only if it is empty.
        $assetDirectory = resource_path(
            'js/vendor/software-billing'
        );

        if (File::isDirectory($assetDirectory)) {
            @rmdir($assetDirectory);
        }

        // Clear Laravel caches.
        $this->call('config:clear');
        $this->call('route:clear');
        $this->call('view:clear');

        $this->newLine();

        $this->info('Software Billing cleanup completed.');

        $this->comment('Database tables and records are preserved.');

        $this->comment(
            'Now run: composer remove shimonahamed/software-billing'
        );

        return self::SUCCESS;
    }
}
