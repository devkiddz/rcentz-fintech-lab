import {
    createChart,
    CandlestickSeries,
    LineSeries,
    AreaSeries,
    HistogramSeries,
    createSeriesMarkers,
} from 'lightweight-charts';

const RCENTZ_CHARTS = new Map();

const parseQuotes = (value) => {
    try {
        const rows = JSON.parse(value || '[]');
        return Array.isArray(rows)
            ? rows
                .map((row) => {
                    const close = Number(row.close ?? row.price);
                    const open = Number(row.open ?? close);
                    const high = Number(row.high ?? Math.max(open, close));
                    const low = Number(row.low ?? Math.min(open, close));

                    return {
                        price: close,
                        open,
                        high,
                        low,
                        close,
                        isCandle: row.open !== undefined && row.high !== undefined && row.low !== undefined && row.close !== undefined,
                        time: row.time ? Math.floor(new Date(row.time).getTime() / 1000) : null,
                        label: row.label || '',
                    };
                })
                .filter((row) =>
                    Number.isFinite(row.price) &&
                    Number.isFinite(row.open) &&
                    Number.isFinite(row.high) &&
                    Number.isFinite(row.low) &&
                    Number.isFinite(row.close) &&
                    Number.isFinite(row.time)
                )
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
    // V3 market truth: if the server supplied OHLC, render it exactly as stored.
    if (quotes.length && quotes.every((quote) => quote.isCandle)) {
        return quotes.map((quote) => ({
            time: quote.time,
            open: quote.open,
            high: quote.high,
            low: quote.low,
            close: quote.close,
        }));
    }

    // Migration-safe fallback for old StockQuote snapshots until the first
    // persisted 15-minute candles have accumulated.
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
            });
            return;
        }

        const candle = buckets.get(bucket);
        candle.high = Math.max(candle.high, quote.price);
        candle.low = Math.min(candle.low, quote.price);
        candle.close = quote.price;
    });

    return [...buckets.values()].sort((a, b) => a.time - b.time);
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


const normalizeAnalysisRows = (rows) => {
    return (Array.isArray(rows) ? rows : [])
        .map((row) => ({
            time: row.time ? Math.floor(new Date(row.time).getTime() / 1000) : null,
            open: Number(row.open),
            high: Number(row.high),
            low: Number(row.low),
            close: Number(row.close),
            volume: Number(row.volume || 0),
            sma20: row.sma20 == null ? null : Number(row.sma20),
            sma50: row.sma50 == null ? null : Number(row.sma50),
            sma200: row.sma200 == null ? null : Number(row.sma200),
        }))
        .filter((row) =>
            Number.isFinite(row.time) &&
            Number.isFinite(row.open) &&
            Number.isFinite(row.high) &&
            Number.isFinite(row.low) &&
            Number.isFinite(row.close)
        )
        .sort((a, b) => a.time - b.time);
};

const parseAnalysis = (value) => {
    try {
        const payload = JSON.parse(value || '{}');
        const timeframes = {};

        Object.entries(payload.timeframes || {}).forEach(([key, rows]) => {
            timeframes[key] = normalizeAnalysisRows(rows);
        });

        return {
            ...payload,
            series: normalizeAnalysisRows(payload.series || []),
            timeframes,
        };
    } catch (_) {
        return { series: [], timeframes: {} };
    }
};

const renderAnalysis = (el) => {
    let payload = parseAnalysis(el.dataset.analysis);
    const compact = el.dataset.compact === 'true';
    const wrapper = el.closest('section');
    const timeframeButtons = wrapper ? wrapper.querySelectorAll('[data-rcentz-timeframe]') : [];
    const modeButtons = wrapper ? wrapper.querySelectorAll('[data-rcentz-chart-mode]') : [];

    let chart = null;
    let activeFrame = el.dataset.defaultTimeframe || payload.default_timeframe || '1d';
    let activeMode = localStorage.getItem('rcentz_stock_chart_mode') || 'line';

    if (!['candles','line','area'].includes(activeMode)) {
        activeMode = 'line';
    }

    const destroyChart = () => {
        if (chart) {
            chart.remove();
            chart = null;
        }
        el.innerHTML = '';
    };

    const setButtonStates = () => {
        timeframeButtons.forEach((button) => {
            const isActive = button.dataset.rcentzTimeframe === activeFrame;
            button.classList.toggle('bg-muted', isActive);
            button.classList.toggle('text-foreground', isActive);
            button.classList.toggle('text-muted-foreground', !isActive);
        });

        modeButtons.forEach((button) => {
            const isActive = button.dataset.rcentzChartMode === activeMode;
            button.classList.toggle('bg-muted', isActive);
            button.classList.toggle('text-foreground', isActive);
            button.classList.toggle('text-muted-foreground', !isActive);
        });
    };

    const renderFrame = (frame = activeFrame, mode = activeMode) => {
        const rows = payload.timeframes?.[frame] || [];
        if (rows.length < 2) return;

        activeFrame = frame;
        activeMode = mode;
        localStorage.setItem('rcentz_stock_chart_mode', mode);

        destroyChart();

        chart = createChart(el, {
            ...chartOptions(compact),
            rightPriceScale: {
                borderColor: 'rgba(148, 163, 184, 0.14)',
                scaleMargins: { top: 0.08, bottom: 0.24 },
                autoScale: true,
            },
            timeScale: {
                borderColor: 'rgba(148, 163, 184, 0.14)',
                timeVisible: ['5m','15m','1h','4h'].includes(frame),
                secondsVisible: false,
                rightOffset: compact ? 1 : 3,
                barSpacing: compact ? 5 : 9,
                minBarSpacing: 2,
            }
        });

        let primarySeries;

        if (mode === 'line') {
            primarySeries = chart.addSeries(LineSeries, {
                color: '#0ea5e9',
                lineWidth: 2,
                priceLineVisible: true,
                lastValueVisible: true,
                crosshairMarkerVisible: true,
            });

            primarySeries.setData(rows.map((row) => ({
                time: row.time,
                value: row.close,
            })));
        } else if (mode === 'area') {
            primarySeries = chart.addSeries(AreaSeries, {
                lineColor: '#0ea5e9',
                topColor: 'rgba(14,165,233,.28)',
                bottomColor: 'rgba(14,165,233,.02)',
                lineWidth: 2,
                priceLineVisible: true,
                lastValueVisible: true,
            });

            primarySeries.setData(rows.map((row) => ({
                time: row.time,
                value: row.close,
            })));
        } else {
            primarySeries = chart.addSeries(CandlestickSeries, {
                upColor: '#10b981',
                downColor: '#ef4444',
                borderUpColor: '#10b981',
                borderDownColor: '#ef4444',
                wickUpColor: '#10b981',
                wickDownColor: '#ef4444',
                priceLineVisible: true,
                lastValueVisible: true,
            });

            primarySeries.setData(rows.map((row) => ({
                time: row.time,
                open: row.open,
                high: row.high,
                low: row.low,
                close: row.close,
            })));
        }

        const addSma = (key, color, width = 2) => {
            const points = rows
                .filter((row) => Number.isFinite(row[key]))
                .map((row) => ({ time: row.time, value: row[key] }));

            if (!points.length) return;

            const line = chart.addSeries(LineSeries, {
                color,
                lineWidth: width,
                priceLineVisible: false,
                lastValueVisible: false,
                crosshairMarkerVisible: false,
            });

            line.setData(points);
        };

        addSma('sma20', '#38bdf8', 2);
        addSma('sma50', '#8b5cf6', 2);
        addSma('sma200', '#a855f7', 1);

        const volumeRows = rows.filter((row) => row.volume > 0);

        if (volumeRows.length) {
            const volume = chart.addSeries(HistogramSeries, {
                priceFormat: { type: 'volume' },
                priceScaleId: '',
            });

            volume.priceScale().applyOptions({
                scaleMargins: { top: 0.80, bottom: 0 },
            });

            volume.setData(volumeRows.map((row) => ({
                time: row.time,
                value: row.volume,
                color: row.close >= row.open
                    ? 'rgba(16,185,129,.38)'
                    : 'rgba(239,68,68,.38)',
            })));
        }

        const avgClose = rows.reduce((sum, row) => sum + row.close, 0) / rows.length;

        const support = Number(payload.support || 0);
        if (
            Number.isFinite(support) &&
            support > 0 &&
            Math.abs(support - avgClose) / avgClose < 0.25
        ) {
            primarySeries.createPriceLine({
                price: support,
                color: '#ef4444',
                lineWidth: 1,
                lineStyle: 2,
                axisLabelVisible: true,
                title: 'Support',
            });
        }

        const resistance = Number(payload.resistance || 0);
        if (
            Number.isFinite(resistance) &&
            resistance > 0 &&
            Math.abs(resistance - avgClose) / avgClose < 0.25
        ) {
            primarySeries.createPriceLine({
                price: resistance,
                color: '#10b981',
                lineWidth: 1,
                lineStyle: 2,
                axisLabelVisible: true,
                title: 'Resistance',
            });
        }

        chart.timeScale().fitContent();
        RCENTZ_CHARTS.set(el, chart);
        setButtonStates();
    };

    const preferred = payload.timeframes?.[activeFrame]?.length >= 2
        ? activeFrame
        : ['1d','15m','5m','1h','4h','1w','1m','3m','1y']
            .find((key) => (payload.timeframes?.[key]?.length || 0) >= 2);

    if (preferred) {
        activeFrame = preferred;
        renderFrame(activeFrame, activeMode);
    }

    timeframeButtons.forEach((button) => {
        if (button.disabled) return;

        button.addEventListener('click', () => {
            renderFrame(button.dataset.rcentzTimeframe, activeMode);
        });
    });

    modeButtons.forEach((button) => {
        button.addEventListener('click', () => {
            renderFrame(activeFrame, button.dataset.rcentzChartMode);
        });
    });

    // V5.14.3: refresh the existing chart instance in place. Button listeners are
    // installed once, so automatic market polling does not accumulate handlers.
    el.__rcentzRefreshAnalysis = (nextPayload) => {
        payload = parseAnalysis(JSON.stringify(nextPayload || {}));
        el.dataset.analysis = JSON.stringify(nextPayload || {});

        const nextPreferred = payload.timeframes?.[activeFrame]?.length >= 2
            ? activeFrame
            : ['1d','15m','5m','1h','4h','1w','1m','3m','1y']
                .find((key) => (payload.timeframes?.[key]?.length || 0) >= 2);

        if (!nextPreferred) return;

        activeFrame = nextPreferred;
        renderFrame(activeFrame, activeMode);
    };
};


window.RcentzCharts = {
    refreshAnalysis(el, payload) {
        if (el && typeof el.__rcentzRefreshAnalysis === 'function') {
            el.__rcentzRefreshAnalysis(payload);
        }
    },
};

const bootCharts = () => {
    document.querySelectorAll('[data-rcentz-candles]').forEach(renderCandles);
    document.querySelectorAll('[data-rcentz-sparkline]').forEach(renderSparkline);
    document.querySelectorAll('[data-rcentz-analysis]').forEach(renderAnalysis);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootCharts);
} else {
    bootCharts();
}
