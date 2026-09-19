<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

class InspectMarketUiAuthority extends Command
{
    protected $signature = 'markets:inspect-ui-authority';
    protected $description = 'Verify shared Instrument list UI authority while preserving rich Stock detail routes.';

    public function handle(): int
    {
        $expected = [
            'instruments.stocks' => [
                'uri' => 'instruments/stocks',
                'action_contains' => 'App\\Http\\Controllers\\MarketInstrumentController@stocks',
                'purpose' => 'Shared registry UI',
            ],
            'instruments.stocks.show' => [
                'uri' => 'instruments/stocks/{stock}',
                'action_contains' => 'App\\Http\\Controllers\\StockController@show',
                'purpose' => 'Rich Stock detail',
            ],
            'admin.instruments.stocks' => [
                'uri' => 'admin/instruments/stocks',
                'action_contains' => 'App\\Http\\Controllers\\Admin\\MarketInstrumentController@stocks',
                'purpose' => 'Shared registry UI',
            ],
            'admin.instruments.stocks.show' => [
                'uri' => 'admin/instruments/stocks/{stock}',
                'action_contains' => 'App\\Http\\Controllers\\Admin\\StockController@show',
                'purpose' => 'Rich Stock detail',
            ],
            'instruments.forex' => [
                'uri' => 'instruments/forex',
                'action_contains' => 'App\\Http\\Controllers\\MarketInstrumentController@forex',
                'purpose' => 'Shared registry UI',
            ],
            'admin.instruments.forex' => [
                'uri' => 'admin/instruments/forex',
                'action_contains' => 'App\\Http\\Controllers\\Admin\\MarketInstrumentController@forex',
                'purpose' => 'Shared registry UI',
            ],
            'instruments.crypto' => [
                'uri' => 'instruments/crypto',
                'action_contains' => 'App\\Http\\Controllers\\MarketInstrumentController@crypto',
                'purpose' => 'Shared registry UI',
            ],
            'admin.instruments.crypto' => [
                'uri' => 'admin/instruments/crypto',
                'action_contains' => 'App\\Http\\Controllers\\Admin\\MarketInstrumentController@crypto',
                'purpose' => 'Shared registry UI',
            ],
        ];

        $rows = [];
        $failures = 0;

        foreach ($expected as $name => $rule) {
            $route = Route::getRoutes()->getByName($name);
            $uriOk = $route && $route->uri() === $rule['uri'];
            $action = $route ? (string) $route->getActionName() : 'MISSING';
            $actionOk = $route && str_contains($action, $rule['action_contains']);
            $ok = $uriOk && $actionOk;

            if (! $ok) {
                $failures++;
            }

            $rows[] = [
                $name,
                $route?->uri() ?? 'MISSING',
                $action,
                $rule['purpose'],
                $ok ? 'OK' : 'FAIL',
            ];
        }

        $this->table(['Route', 'URI', 'Action', 'UI Authority', 'State'], $rows);

        if ($failures > 0) {
            $this->error('MARKET_UI_AUTHORITY_M3_2_FAILED');
            return self::FAILURE;
        }

        $this->info('MARKET_UI_AUTHORITY_M3_2_OK');
        return self::SUCCESS;
    }
}
