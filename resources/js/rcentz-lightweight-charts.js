import {
    createChart,
    CandlestickSeries,
    LineSeries,
    HistogramSeries,
    createSeriesMarkers,
} from 'lightweight-charts';

const RCENTZ_CHARTS = new Map();

const parseQuotes = (value) => {
    try {
        const rows = JSON.parse(value || '[]');
        return Array.isArray(rows)
            ? rows
                .map((row) => ({
                    price: Number(row.price),
                    time: row.time ? Math.floor(new Date(row.time).getTime() / 1000) : null,
                    label: row.label || '',
                }))
                .filter((row) => Number.isFinite(row.price) && Number.isFinite(row.time))
                .sort((a, b) => a.time - b.time)
            : [];
    } catch (_) {
        return [];
    }
};

const parseExecutions = (value) => {
    try {
        const rows = JSON.parse(value || '[]');
        return Array.isArray(rows)
            ? rows
                .map((row) => ({
                    ...row,
                    price: Number(row.price),
                    amount: Number(row.amount || 0),
                    quantity: Number(row.quantity || 0),
                    time: row.time ? Math.floor(new Date(row.time).getTime() / 1000) : null,
                }))
                .filter((row) => Number.isFinite(row.price) && Number.isFinite(row.time))
            : [];
    } catch (_) {
        return [];
    }
};

// Build honest OHLC bars from the snapshots we actually stored.
// 15-minute buckets give each bar multiple samples when the 5-minute scheduler is running.
const toCandles = (quotes, bucketSeconds = 900) => {
    const buckets = new Map();

    quotes.forEach((quote) => {
        const bucket = Math.floor(quote.time / bucketSeconds) * bucketSeconds;
        if (!buckets.has(bucket)) {
            buckets.set(bucket, {
                time: bucket,
                open: quote.price,
                high: quote.price,
                low: quote.price,
                close: quote.price,
                samples: 1,
            });
            return;
        }

        const candle = buckets.get(bucket);
        candle.high = Math.max(candle.high, quote.price);
        candle.low = Math.min(candle.low, quote.price);
        candle.close = quote.price;
        candle.samples += 1;
    });

    const candles = [...buckets.values()].sort((a, b) => a.time - b.time);

    // If we only have one sample in a bucket, use the previous close as that bar's open.
    // This preserves direction without inventing highs/lows beyond observed prices.
    for (let i = 1; i < candles.length; i++) {
        if (candles[i].samples === 1) {
            candles[i].open = candles[i - 1].close;
            candles[i].high = Math.max(candles[i].high, candles[i].open);
            candles[i].low = Math.min(candles[i].low, candles[i].open);
        }
    }

    return candles.map(({ samples, ...candle }) => candle);
};

const chartOptions = (compact = false) => ({
    autoSize: true,
    layout: {
        background: { color: 'transparent' },
        textColor: '#94a3b8',
        fontSize: compact ? 10 : 11,
    },
    grid: {
        vertLines: { color: 'rgba(148, 163, 184, 0.08)' },
        horzLines: { color: 'rgba(148, 163, 184, 0.08)' },
    },
    rightPriceScale: {
        borderColor: 'rgba(148, 163, 184, 0.16)',
        scaleMargins: { top: 0.12, bottom: 0.12 },
    },
    timeScale: {
        borderColor: 'rgba(148, 163, 184, 0.16)',
        timeVisible: true,
        secondsVisible: false,
        rightOffset: compact ? 2 : 4,
        barSpacing: compact ? 7 : 10,
        minBarSpacing: compact ? 3 : 5,
    },
    crosshair: {
        vertLine: { color: 'rgba(148, 163, 184, .28)', width: 1 },
        horzLine: { color: 'rgba(148, 163, 184, .28)', width: 1 },
    },
    handleScroll: true,
    handleScale: true,
});

const addExecutionMarkers = (series, executions) => {
    if (!executions.length) return;

    const markers = executions.map((execution) => {
        const sell = String(execution.action || '').toLowerCase() === 'sell';
        return {
            time: execution.time,
            position: sell ? 'aboveBar' : 'belowBar',
            color: sell ? '#ef4444' : '#10b981',
            shape: sell ? 'arrowDown' : 'arrowUp',
            text: sell ? 'SELL' : 'BUY',
        };
    });

    createSeriesMarkers(series, markers);
};

const renderCandles = (el) => {
    const quotes = parseQuotes(el.dataset.quotes || el.dataset.chart);
    if (!quotes.length) return;

    const compact = el.dataset.compact === 'true';
    const chart = createChart(el, chartOptions(compact));
    const candles = toCandles(quotes);

    const candleSeries = chart.addSeries(CandlestickSeries, {
        upColor: '#10b981',
        downColor: '#ef4444',
        borderUpColor: '#10b981',
        borderDownColor: '#ef4444',
        wickUpColor: '#10b981',
        wickDownColor: '#ef4444',
        priceLineVisible: true,
        lastValueVisible: true,
    });

    candleSeries.setData(candles);

    const executions = parseExecutions(el.dataset.executions);
    addExecutionMarkers(candleSeries, executions);

    const averageEntry = Number(el.dataset.averageEntry || 0);
    if (Number.isFinite(averageEntry) && averageEntry > 0) {
        candleSeries.createPriceLine({
            price: averageEntry,
            color: '#f59e0b',
            lineWidth: 1,
            lineStyle: 2,
            axisLabelVisible: true,
            title: compact ? 'Avg' : 'Avg Entry',
        });
    }

    const entry = Number(el.dataset.entry || 0);
    if (Number.isFinite(entry) && entry > 0) {
        candleSeries.createPriceLine({
            price: entry,
            color: '#f59e0b',
            lineWidth: 1,
            lineStyle: 2,
            axisLabelVisible: true,
            title: 'Entry',
        });
    }

    chart.timeScale().fitContent();
    RCENTZ_CHARTS.set(el, chart);
};

const renderSparkline = (el) => {
    const quotes = parseQuotes(el.dataset.quotes || el.dataset.chart);
    if (!quotes.length) return;

    const chart = createChart(el, {
        ...chartOptions(true),
        grid: { vertLines: { visible: false }, horzLines: { visible: false } },
        leftPriceScale: { visible: false },
        rightPriceScale: { visible: false },
        timeScale: { visible: false },
        crosshair: { mode: 0 },
        handleScroll: false,
        handleScale: false,
    });

    const line = chart.addSeries(LineSeries, {
        color: '#0ea5e9',
        lineWidth: 2,
        priceLineVisible: false,
        lastValueVisible: false,
    });

    line.setData(quotes.map((q) => ({ time: q.time, value: q.price })));

    // Red/green momentum bars under the sparkline.
    const bars = chart.addSeries(HistogramSeries, {
        priceFormat: { type: 'volume' },
        priceScaleId: '',
        base: 0,
    });

    bars.priceScale().applyOptions({
        scaleMargins: { top: 0.78, bottom: 0 },
    });

    bars.setData(quotes.slice(1).map((q, index) => {
        const previous = quotes[index];
        const delta = q.price - previous.price;
        return {
            time: q.time,
            value: Math.abs(delta),
            color: delta >= 0 ? 'rgba(16,185,129,.55)' : 'rgba(239,68,68,.65)',
        };
    }));

    chart.timeScale().fitContent();
    RCENTZ_CHARTS.set(el, chart);
};

const bootCharts = () => {
    document.querySelectorAll('[data-rcentz-candles]').forEach(renderCandles);
    document.querySelectorAll('[data-rcentz-sparkline]').forEach(renderSparkline);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootCharts);
} else {
    bootCharts();
}
