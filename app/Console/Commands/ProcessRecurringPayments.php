<?php

namespace App\Console\Commands;

use App\Services\RecurringPaymentService;
use Illuminate\Console\Command;

class ProcessRecurringPayments extends Command
{
    protected $signature = 'payease:process-recurring-payments';
    protected $description = 'Process all due recurring payments';

    public function handle(RecurringPaymentService $service): int
    {
        $this->info('Processing recurring payments...');

        try {
            $processed = $service->processDue();
            $this->info("Processed {$processed} recurring payment(s).");
            return Command::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Recurring payment processing failed: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }
}
