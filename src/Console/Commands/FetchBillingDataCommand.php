<?php

namespace SoftwareBilling\Console\Commands;

use Illuminate\Console\Command;
use SoftwareBilling\Services\BillingApiService;

class FetchBillingDataCommand extends Command
{
    protected $signature = 'software-billing:fetch';

    protected $description = 'Fetch billing data from configured API';

    public function handle(BillingApiService $billingApiService): int
    {
        $this->info('Starting billing API request...');

        $result = $billingApiService->fetch();

        if (!$result['success']) {
            $this->error($result['message']);

            return self::FAILURE;
        }

        $this->info($result['message']);

        $this->info(
            'Total bills: ' . ($result['total_bills'] ?? 0)
        );

        return self::SUCCESS;
    }
}
