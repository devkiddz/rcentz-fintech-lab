<?php

function patchFile(string $path, array $replacements): void
{
    if (! is_file($path)) throw new RuntimeException("Missing file: {$path}");

    $text = file_get_contents($path);

    foreach ($replacements as [$old,$new,$label]) {
        if (! str_contains($text,$old)) {
            throw new RuntimeException("Patch target not found ({$label}) in {$path}");
        }

        $text = str_replace($old,$new,$text,$count);

        if ($count < 1) {
            throw new RuntimeException("Patch failed ({$label})");
        }
    }

    file_put_contents($path,$text);
}

$root = dirname(__DIR__);
$stockController = $root.'/app/Http/Controllers/StockController.php';
$tradingController = $root.'/app/Http/Controllers/TradingController.php';

@mkdir($root.'/storage/app',0777,true);
@copy($stockController,$root.'/storage/app/StockController.v4_2.backup.php');
@copy($tradingController,$root.'/storage/app/TradingController.v4_2.backup.php');

patchFile($stockController, [
    [
        "use App\\Services\\StockDataService;\n",
        "use App\\Services\\StockDataService;\nuse App\\Services\\StockAnalysisService;\n",
        'stock analysis import'
    ],
    [
        "        // Get stock performance data (in real app, this would come from external API)\n        \$performanceData = \$this->getPerformanceData(\$stock);\n",
        "        // Stored-state market analysis for charts, moving averages and signal context.\n        \$analysis = app(StockAnalysisService::class)->forStock(\$stock);\n\n        // Legacy performance payload retained temporarily for untouched consumers.\n        \$performanceData = \$this->getPerformanceData(\$stock);\n",
        'stock analysis payload'
    ],
    [
        "            'newsFromApi'\n        ));",
        "            'newsFromApi',\n            'analysis'\n        ));",
        'stock compact'
    ],
]);

patchFile($tradingController, [
    [
        "use App\\Services\\StockTradePlanService;\n",
        "use App\\Services\\StockTradePlanService;\nuse App\\Services\\StockAnalysisService;\n",
        'trading analysis import'
    ],
    [
        "        // Get chart data for the stock\n        \$chartData = \$this->getStockChartData(\$stock->symbol);\n\n        return view('trading.buy', compact('stock', 'wallet', 'userHolding', 'chartData'));",
        "        // Legacy chart payload retained while the new analysis engine owns the workstation chart.\n        \$chartData = \$this->getStockChartData(\$stock->symbol);\n        \$analysis = app(StockAnalysisService::class)->forStock(\$stock);\n\n        return view('trading.buy', compact('stock', 'wallet', 'userHolding', 'chartData', 'analysis'));",
        'buy analysis'
    ],
    [
        "        // Get chart data for 1M period by default\n        \$chartData = \$this->getStockChartData(\$stock->symbol, '1m');\n        \n        return view('trading.sell', compact('stock', 'wallet', 'holding', 'chartData'));",
        "        // Legacy chart payload retained while the new analysis engine owns the workstation chart.\n        \$chartData = \$this->getStockChartData(\$stock->symbol, '1m');\n        \$analysis = app(StockAnalysisService::class)->forStock(\$stock);\n        \n        return view('trading.sell', compact('stock', 'wallet', 'holding', 'chartData', 'analysis'));",
        'sell analysis'
    ],
]);

echo "V4.2 trading workspace wiring complete.\n";
echo "Backups saved under storage/app/*v4_2.backup.php\n";
