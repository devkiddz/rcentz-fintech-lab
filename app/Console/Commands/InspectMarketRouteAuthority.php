<?php

namespace App\Console\Commands;

use App\Models\MarketInstrument;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class InspectMarketRouteAuthority extends Command
{
    protected $signature = 'markets:inspect-route-authority';
    protected $description = 'Verify canonical MarketInstrument route hierarchy and compatibility endpoints.';

    public function handle(): int
    {
        $required = [
            'instruments.index' => 'instruments',
            'instruments.stocks' => 'instruments/stocks',
            'instruments.stocks.show' => 'instruments/stocks/{stock}',
            'instruments.forex' => 'instruments/forex',
            'instruments.forex.show' => 'instruments/forex/{symbol}',
            'instruments.crypto' => 'instruments/crypto',
            'instruments.crypto.show' => 'instruments/crypto/{symbol}',
            'admin.instruments.index' => 'admin/instruments',
            'admin.instruments.stocks' => 'admin/instruments/stocks',
            'admin.instruments.stocks.show' => 'admin/instruments/stocks/{stock}',
            'admin.instruments.forex' => 'admin/instruments/forex',
            'admin.instruments.forex.show' => 'admin/instruments/forex/{symbol}',
            'admin.instruments.crypto' => 'admin/instruments/crypto',
            'admin.instruments.crypto.show' => 'admin/instruments/crypto/{symbol}',
        ];

        $rows = [];
        $failures = 0;

        foreach ($required as $name => $expectedUri) {
            $route = Route::getRoutes()->getByName($name);
            $actual = $route?->uri() ?? 'MISSING';
            $ok = $route && $actual === $expectedUri;

            if (! $ok) {
                $failures++;
            }

            $rows[] = [$name, $actual, $ok ? 'OK' : 'FAIL'];
        }

        $this->table(['Route', 'URI', 'State'], $rows);

        $samples = [];

        $stock = MarketInstrument::query()->where('asset_class', 'stock')->where('is_active', true)->orderBy('id')->first();
        if ($stock) {
            $samples[] = [
                'STOCK',
                $stock->display_symbol,
                route('admin.instruments.stocks.show', ['stock' => $stock->symbol], false),
            ];
        }

        $forex = MarketInstrument::query()->where('asset_class', 'forex')->where('is_active', true)->orderBy('id')->first();
        if ($forex) {
            $samples[] = [
                'FOREX',
                $forex->display_symbol,
                route('admin.instruments.forex.show', ['symbol' => $forex->symbol], false),
            ];
        }

        if ($samples) {
            $this->table(['Asset', 'Instrument', 'Canonical Admin URL'], $samples);
        }

        $compatibility = [
            'stocks.index',
            'stocks.show',
            'admin.stocks.index',
            'admin.stocks.show',
            'instruments.show',
            'admin.instruments.show',
        ];

        $compatRows = [];
        foreach ($compatibility as $name) {
            $exists = Route::getRoutes()->getByName($name) !== null;
            if (! $exists) {
                $failures++;
            }
            $compatRows[] = [$name, $exists ? 'PRESENT' : 'MISSING'];
        }

        $this->table(['Compatibility Route', 'State'], $compatRows);

        if ($failures > 0) {
            $this->error('MARKET_ROUTE_AUTHORITY_M3_1_FAILED');
            return self::FAILURE;
        }

        $this->info('MARKET_ROUTE_AUTHORITY_M3_1_OK');
        return self::SUCCESS;
    }
}
