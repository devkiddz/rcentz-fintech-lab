<?php

namespace App\Services;

use App\Services\Legacy\LegacyStockExecutionEngine;

/**
 * @deprecated Compatibility alias for the isolated legacy StockTradePlan engine.
 *             New trading code must use StockTradeExecutor + TradePositionService.
 */
class StockExecutionService extends LegacyStockExecutionEngine
{
}
