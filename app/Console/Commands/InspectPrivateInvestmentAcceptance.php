<?php

namespace App\Console\Commands;

use App\Models\PrivateInvestmentAuditLog;
use App\Models\PrivateInvestmentHolding;
use App\Models\PrivateInvestmentInstrument;
use App\Models\PrivateInvestmentTransaction;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InspectPrivateInvestmentAcceptance extends Command
{
    protected $signature = 'investment:inspect-ms4-acceptance';
    protected $description = 'Inspect MS4 Private Investment order, lifecycle, wallet, audit and idempotency acceptance evidence.';

    public function handle(): int
    {
        if (! Schema::hasColumn('private_investment_transactions', 'idempotency_key')) {
            $this->error('private_investment_transactions.idempotency_key is missing. Run migrations first.');
            return self::FAILURE;
        }

        $cases = [
            ['email' => 'qa.invest.income@rcentz.test', 'symbol' => 'QAIPF', 'expected' => 'closed', 'mode' => 'ROUND TRIP'],
            ['email' => 'qa.invest.locked@rcentz.test', 'symbol' => 'QALIN', 'expected' => 'active', 'mode' => 'LOCKED'],
            ['email' => 'qa.invest.partial@rcentz.test', 'symbol' => 'QATPB', 'expected' => 'active', 'mode' => 'PARTIAL'],
        ];

        $rows = [];
        $failed = false;
        foreach ($cases as $case) {
            $user = User::query()->where('email', $case['email'])->first();
            $instrument = PrivateInvestmentInstrument::query()->where('symbol', $case['symbol'])->first();
            if (! $user || ! $instrument) {
                $rows[] = [$case['mode'], $case['email'], $case['symbol'], 'MISSING', '—', '—', '—'];
                $failed = true;
                continue;
            }

            $holding = PrivateInvestmentHolding::query()
                ->where('user_id', $user->id)
                ->where('instrument_id', $instrument->id)
                ->first();
            $completed = PrivateInvestmentTransaction::query()
                ->where('user_id', $user->id)
                ->where('instrument_id', $instrument->id)
                ->where('status', 'completed')
                ->count();
            $audit = PrivateInvestmentAuditLog::query()
                ->where('target_user_id', $user->id)
                ->where('instrument_id', $instrument->id)
                ->count();

            if (! $holding || $holding->status !== $case['expected']) {
                $failed = true;
            }
            if ($case['mode'] === 'LOCKED') {
                if (! $holding?->locked_until || ! now()->lt($holding->locked_until)) {
                    $failed = true;
                }
                $earlyRedemption = PrivateInvestmentTransaction::query()
                    ->where('user_id', $user->id)
                    ->where('idempotency_key', 'qa-pinv:locked-holding:early-redemption:v1')
                    ->exists();
                if ($earlyRedemption) {
                    $failed = true;
                }
            }

            $rows[] = [
                $case['mode'],
                $case['email'],
                $case['symbol'],
                $holding?->status ?? '—',
                $holding ? number_format((float) $holding->units, 6) : '—',
                $completed,
                $audit,
            ];
        }

        $this->table(['Case', 'Customer', 'Instrument', 'Holding', 'Units', 'Completed tx', 'Audit rows'], $rows);

        $checks = [
            'QA transactions missing wallet reference' => PrivateInvestmentTransaction::query()
                ->whereHas('user', fn ($q) => $q->where('email', 'like', 'qa.invest.%@rcentz.test'))
                ->where('status', 'completed')
                ->get()
                ->filter(fn ($tx) => ! WalletTransaction::query()->where('reference_id', $tx->reference)->exists())
                ->count(),
            'QA transactions missing audit record' => PrivateInvestmentTransaction::query()
                ->whereHas('user', fn ($q) => $q->where('email', 'like', 'qa.invest.%@rcentz.test'))
                ->where('status', 'completed')
                ->get()
                ->filter(fn ($tx) => ! PrivateInvestmentAuditLog::query()->where('reference', $tx->reference)->exists())
                ->count(),
            'Duplicate QA user/idempotency keys' => DB::table('private_investment_transactions as t')
                ->join('users as u', 'u.id', '=', 't.user_id')
                ->where('u.email', 'like', 'qa.invest.%@rcentz.test')
                ->whereNotNull('t.idempotency_key')
                ->selectRaw('t.user_id, t.idempotency_key, COUNT(*) as total')
                ->groupBy('t.user_id', 't.idempotency_key')
                ->having('total', '>', 1)
                ->get()
                ->count(),
            'QA instruments visible in public catalogue' => PrivateInvestmentInstrument::query()
                ->whereIn('symbol', ['QAIPF', 'QALIN', 'QATPB'])
                ->where('is_visible', true)
                ->count(),
        ];

        $this->newLine();
        $this->table(
            ['MS4 authority check', 'Count'],
            collect($checks)->map(fn ($count, $label) => [$label, $count])->values()->all()
        );

        if ($failed || collect($checks)->contains(fn ($count) => (int) $count > 0)) {
            $this->error('MS4_PRIVATE_INVESTMENT_ACCEPTANCE_FAILED');
            return self::FAILURE;
        }

        $this->info('MS4 Private Investments are READY with persistent subscribe/redeem, lock, lifecycle, wallet, audit and replay-safe idempotency evidence.');
        return self::SUCCESS;
    }
}
