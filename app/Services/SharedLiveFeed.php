<?php
namespace App\Services;

use App\Models\MarketInstrument;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

/** Local trial: one durable source for marks, OHLC and simulated execution. */
class SharedLiveFeed
{
    public function symbol(MarketInstrument $instrument): ?string
    {
        $base = strtoupper((string) $instrument->base_asset);
        $quote = strtoupper((string) $instrument->quote_asset);
        if ($quote !== 'USD') return null;
        if ($instrument->isForex() && $base === 'EUR') return 'EUR/USD';
        if ($instrument->isCommodity() && $base === 'XAU') return 'XAU/USD';
        return null;
    }

    public function applies(MarketInstrument $instrument): bool
    {
        if (!app()->environment('local','testing') || !$this->symbol($instrument)) return false;
        if (!Schema::hasTable('shared_live_quotes')) return false;
        // Managed instruments stay managed when a worker stops; no silent fallback.
        return DB::table('shared_live_quotes')->where('instrument_id', $instrument->id)->exists();
    }

    public function state(MarketInstrument $instrument): array
    {
        $row = DB::table('shared_live_quotes')->where('instrument_id', $instrument->id)->first();
        if (!$row || $row->symbol !== $this->symbol($instrument)) throw new RuntimeException('Shared live feed identity is unavailable.');
        $price = self::positive($row->price);
        $at = CarbonImmutable::parse($row->quoted_at, 'UTC');
        $age = now()->utc()->timestamp - $at->timestamp;
        $status = $row->health;
        if (CarbonImmutable::parse($row->lease_until, 'UTC')->timestamp <= now()->utc()->timestamp) $status = 'worker_stopped';
        elseif ($age > 300 || $age < -30) $status = 'stale';
        return ['price'=>$price, 'rate'=>$price, 'bid'=>$price, 'ask'=>$price,
            'captured_at'=>$at, 'age_seconds'=>$age, 'quote_status'=>$status,
            'source'=>'twelve_data_shared', 'marketplace'=>'live'];
    }

    public function mark(MarketInstrument $instrument): float
    {
        return $this->state($instrument)['price'];
    }

    public function execution(MarketInstrument $instrument): array
    {
        $state = $this->state($instrument);
        if ($state['quote_status'] !== 'fresh') throw new RuntimeException('Live feed is stale or stopped. Restart the live feed worker before trading.');
        return $state;
    }

    public function analysis(MarketInstrument $instrument): array
    {
        $state = $this->state($instrument);
        $frames = [];
        foreach (['5m','4h','1d'] as $interval) {
            $rows = DB::table('shared_live_bars')->where('instrument_id',$instrument->id)
                ->where('interval',$interval)->orderByDesc('bar_at')->limit(300)->get()->reverse()->values();
            $frames[$interval] = $rows->map(fn ($r) => ['time'=>CarbonImmutable::parse($r->bar_at,'UTC')->toIso8601String(),
                'open'=>(float)$r->open, 'high'=>(float)$r->high, 'low'=>(float)$r->low, 'close'=>(float)$r->close, 'volume'=>0])->all();
        }
        $series = $frames['4h'];
        $recent = array_slice($series,-30);
        $first = $series[0]['close'] ?? $state['price'];
        $last = $series[count($series)-1]['close'] ?? $state['price'];
        $momentum = $first > 0 ? ($last-$first)/$first*100 : 0;
        return ['source'=>'twelve_data_shared', 'analysis_source'=>'twelve_data_shared',
            'current_price'=>$state['price'], 'captured_at'=>$state['captured_at']->toIso8601String(),
            'quote_status'=>$state['quote_status'], 'quote_age_seconds'=>$state['age_seconds'],
            'previous_close'=>$series[count($series)-2]['close'] ?? $last,
            'series'=>$series, 'timeframes'=>$frames, 'default_timeframe'=>'4h', 'point_series'=>false,
            'has_chart'=>count($series)>=2, 'support'=>$recent ? min(array_column($recent,'low')) : null,
            'resistance'=>$recent ? max(array_column($recent,'high')) : null,
            'sma20'=>null, 'sma50'=>null, 'sma200'=>null,
            'momentum_percent'=>$momentum, 'momentum_label'=>$momentum>=0?'Bullish':'Bearish',
            'trend'=>$momentum>=0?'Bullish':'Bearish', 'risk_reward'=>null,
            'volume_current'=>0, 'volume_average'=>0, 'volume_vs_average'=>null];
    }

    public static function positive(mixed $value): float
    {
        if (!is_numeric($value) || !is_finite((float)$value) || (float)$value<=0) throw new RuntimeException('Provider price is invalid.');
        return (float)$value;
    }

    public static function validateQuote(array $data, string $symbol): array
    {
        if (($data['symbol']??null)!==$symbol || !is_numeric($data['timestamp']??null)) throw new RuntimeException('Provider quote identity or timestamp is invalid.');
        $price = self::positive($data['rate']??null);
        $stamp = (int)$data['timestamp'];
        $age = now()->utc()->timestamp-$stamp;
        if ($age < -30 || $age > 300) throw new RuntimeException('Provider quote is stale or future-dated.');
        return ['price'=>$price,'quoted_at'=>CarbonImmutable::createFromTimestampUTC($stamp)->format('Y-m-d H:i:s')];
    }

    public static function validateBars(array $data, string $symbol, string $interval): array
    {
        if (($data['meta']['symbol']??null)!==$symbol || ($data['meta']['interval']??null)!==$interval) throw new RuntimeException('Provider history identity or interval is invalid.');
        $rows = [];
        foreach (($data['values']??[]) as $row) {
            if (!is_string($row['datetime']??null) || !preg_match('/^\d{4}-\d{2}-\d{2}( \d{2}:\d{2}:\d{2})?$/D',$row['datetime'])) throw new RuntimeException('Provider bar time is invalid.');
            $at = CarbonImmutable::parse($row['datetime'],'UTC');
            if ($at->timestamp > now()->utc()->timestamp+30) throw new RuntimeException('Provider bar is future-dated.');
            $bar = ['bar_at'=>$at->format('Y-m-d H:i:s')];
            foreach (['open','high','low','close'] as $field) $bar[$field] = self::positive($row[$field]??null);
            if ($bar['high']<max($bar['open'],$bar['close'],$bar['low']) || $bar['low']>min($bar['open'],$bar['close'],$bar['high'])) throw new RuntimeException('Provider OHLC is inconsistent.');
            $rows[$bar['bar_at']] = $bar;
        }
        ksort($rows);
        if (count($rows)<2) throw new RuntimeException('Provider history has insufficient bars.');
        return array_values($rows);
    }
}
