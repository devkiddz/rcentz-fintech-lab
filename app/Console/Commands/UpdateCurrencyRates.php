<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CurrencyService;

class UpdateCurrencyRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'currency:update-rates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update currency exchange rates from API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating currency exchange rates...');
        
        $currencyService = new CurrencyService();
        $success = $currencyService->updateRates();
        
        if ($success) {
            $this->info('Currency rates updated successfully!');
            return Command::SUCCESS;
        } else {
            $this->error('Failed to update currency rates. Check logs for details.');
            return Command::FAILURE;
        }
    }
}
