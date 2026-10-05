<?php

namespace App\Console\Commands;

use App\Services\PaperTrading\PaperLifecycleService;
use Illuminate\Console\Command;
use Throwable;

final class ProcessPaperPositions extends Command
{
    protected $signature = 'paper-positions:process {--marketplace= : controlled or live}';
    protected $description = 'Run one gated paper-position exit pass; never starts a worker or ticks prices';

    public function handle(PaperLifecycleService $lifecycle): int
    {
        try {
            $marketplace = $this->option('marketplace') ?: null;
            $stats = $lifecycle->process(null, $marketplace);
            $this->info(json_encode($stats, JSON_THROW_ON_ERROR));
            return ($stats['failed'] + ($stats['copy_retry']['failed'] ?? 0)) > 0 ? self::FAILURE : self::SUCCESS;
        } catch (Throwable $error) {
            $this->error($error->getMessage());
            return self::FAILURE;
        }
    }
}
