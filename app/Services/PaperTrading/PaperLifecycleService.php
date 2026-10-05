<?php
declare(strict_types=1);

namespace App\Services\PaperTrading;

use App\Models\TradePosition;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;
use Throwable;

/** One explicit pass; does not fetch external feeds, tick prices or start workers. */
final class PaperLifecycleService
{
    public function __construct(private PaperBrokerService $broker) {}

    public function process(?array $positionIds = null, ?string $marketplace = null): array
    {
        if (!config('paper_trading.lifecycle_enabled', false)) {
            throw new RuntimeException('Paper lifecycle activation is not enabled.');
        }
        if ($marketplace !== null && !in_array($marketplace, ['live', 'controlled'], true)) {
            throw new InvalidArgumentException('Marketplace must be live or controlled.');
        }
        if ($positionIds !== null) {
            foreach ($positionIds as $id) {
                if (filter_var($id, FILTER_VALIDATE_INT) === false || (int)$id < 1) {
                    throw new InvalidArgumentException('Position IDs must be positive integers.');
                }
            }
        }
        $stats = ['checked'=>0, 'closed'=>0, 'unchanged'=>0, 'failed'=>0,
            'stop_loss'=>0, 'take_profit'=>0, 'time_expiry'=>0, 'margin_exhausted'=>0,
            'bot_subscription_expiry'=>0, 'copy_contract_expiry'=>0,
            'copy_retry'=>null, 'failed_position_ids'=>[]];
        $contexts = ['broker_order','manual_trade'];
        if (config('paper_trading.copy_enabled',false)) { $contexts = array_merge($contexts,['copy_strategy','copy_relationship']); }
        if (config('paper_trading.bots_enabled',false)) { $contexts[] = 'trading_bot'; }
        $query = TradePosition::query()->with('user')
            ->where('metadata->execution_model', PaperBrokerService::MODEL)
            ->whereIn('context_type', $contexts)
            ->whereIn('status', ['open', 'exit_queued'])->where('open_quantity', '>', 0);
        if ($positionIds !== null) { $query->whereIn('id', $positionIds); }
        if ($marketplace !== null) { $query->where('marketplace', $marketplace); }
        // Bound this pass to the positions that existed when it started.
        $upperId = (clone $query)->max('id');
        if ($upperId === null) { return $this->retryCopies($stats,$positionIds); }
        $query->where('id', '<=', $upperId)->chunkById(100, function ($positions) use (&$stats) {
            foreach ($positions as $position) {
                $stats['checked']++;
                try {
                    $order = $this->broker->closeTriggered($position->user, $position,
                        'paper-auto-close-'.$position->id);
                    if ($order === null) {
                        $stats['unchanged']++;
                        continue;
                    }
                    $reason = $position->refresh()->exit_reason;
                    $stats['closed']++;
                    if (array_key_exists($reason, $stats)) { $stats[$reason]++; }
                } catch (Throwable $error) {
                    $stats['failed']++;
                    $stats['failed_position_ids'][] = (int)$position->id;
                    Log::warning('Paper lifecycle position deferred', [
                        'position_id'=>$position->id, 'error_class'=>get_class($error),
                    ]);
                }
            }
        });
        return $this->retryCopies($stats,$positionIds);
    }

    private function retryCopies(array $stats, ?array $positionIds): array
    {
        if (config('paper_trading.copy_enabled',false)) {
            $stats['copy_retry'] = app(PaperCopyTradingService::class)->retryExits($positionIds);
        }
        return $stats;
    }
}
