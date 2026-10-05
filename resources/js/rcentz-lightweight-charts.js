import {validPeriod,movingAverage,zoomRange,executionMarkers} from './chart-controls-math';
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
    const timeButtons = wrapper?.querySelectorAll('[data-rcentz-timeframe]') || [];
    const modeButtons = wrapper?.querySelectorAll('[data-rcentz-chart-mode]') || [];
    const precision = Math.max(0, Math.min(8, Number(el.dataset.pricePrecision || 2)));
    const priceFormat = {type:'price',precision,minMove:Math.pow(10,-precision)};
    const controls = wrapper?.querySelector('[data-chart-controls]');
    let chart, primary, averages=[], volume, markers, levelLines=[];
    let frame = payload.source==='twelve_data_shared' ? payload.default_timeframe : (el.dataset.defaultTimeframe || payload.default_timeframe || '1d');
    let mode = localStorage.getItem('rcentz_stock_chart_mode') || 'candles';
    if (!['line','area','candles'].includes(mode)) mode='candles';
    const pointOnly = () => payload.point_series === true && payload.asset_class === 'commodity' && payload.marketplace === 'live';
    if(pointOnly()) mode='line';
    const checked = (name, fallback=true) => controls?.querySelector('[data-chart-toggle="'+name+'"]')?.checked ?? fallback;
    const period = (index) => validPeriod(controls?.querySelector('[data-chart-period="'+index+'"]')?.value, [20,50,200][index]);
    const capture = () => chart ? {logical:chart.timeScale().getVisibleLogicalRange(),
        auto:chart.priceScale('right').options().autoScale,
        price:chart.priceScale('right').getVisibleRange?.()} : null;
    const restore = (view) => {
        if (!view) return;
        if (view.logical) chart.timeScale().setVisibleLogicalRange(view.logical);
        chart.priceScale('right').applyOptions({autoScale:view.auto});
        if (!view.auto && view.price) chart.priceScale('right').setVisibleRange?.(view.price);
    };
    const build = () => {
        if (chart) chart.remove();
        el.innerHTML='';levelLines=[];
        chart=createChart(el,{...chartOptions(compact),rightPriceScale:{borderColor:'rgba(148,163,184,.14)',scaleMargins:{top:.08,bottom:.24},autoScale:checked('auto')},
            timeScale:{borderColor:'rgba(148,163,184,.14)',timeVisible:['5m','15m','1h','4h'].includes(frame),rightOffset:3,barSpacing:9,minBarSpacing:2}});
        const type=mode==='candles'?CandlestickSeries:mode==='area'?AreaSeries:LineSeries;
        primary=chart.addSeries(type,{priceFormat,lineWidth:2,color:'#0ea5e9',lineColor:'#0ea5e9',topColor:'rgba(14,165,233,.28)',bottomColor:'rgba(14,165,233,.02)',
            upColor:'#10b981',downColor:'#ef4444',borderUpColor:'#10b981',borderDownColor:'#ef4444',wickUpColor:'#10b981',wickDownColor:'#ef4444'});
        averages=['#38bdf8','#8b5cf6','#a855f7'].map(color=>chart.addSeries(LineSeries,{priceFormat,color,lineWidth:2,priceLineVisible:false,lastValueVisible:false,crosshairMarkerVisible:false}));
        volume=chart.addSeries(HistogramSeries,{priceFormat:{type:'volume'},priceScaleId:''});
        volume.priceScale().applyOptions({scaleMargins:{top:.8,bottom:0}});
        markers=createSeriesMarkers(primary,[]);
        RCENTZ_CHARTS.set(el,chart);
    };
    const draw = (reset=false, rebuild=false) => {
        if(pointOnly()) { if(mode==='candles'){mode='line';rebuild=true;} modeButtons.forEach(b=>{b.disabled=b.dataset.rcentzChartMode==='candles';b.title=b.disabled?'Provider supplies price points, not OHLC candles.':'';}); }
        const rows=payload.timeframes?.[frame] || [];
        if (rows.length<2) return;
        const view=capture();
        if (!chart || rebuild) build();
        const currentAuto=rebuild ? checked('auto') : (view?.auto ?? checked('auto'));
        primary.setData(rows.map(row=>mode==='candles'?{time:row.time,open:row.open,high:row.high,low:row.low,close:row.close}:{time:row.time,value:row.close}));
        const legend=[];
        averages.forEach((series,index)=>{
            const enabled=checked('ma'+index);
            const count=period(index);
            const defaults=[20,50,200];
            let points=movingAverage(rows,count);
            // Server-provided default averages can include history preceding this chart window.
            if (count===defaults[index]) {
                const stored=rows.filter(row=>Number.isFinite(row['sma'+count])).map(row=>({time:row.time,value:row['sma'+count]}));
                if (stored.length) points=stored;
            }
            series.setData(points);series.applyOptions({visible:enabled});
            if (enabled) legend.push('SMA '+count+(points.length?' '+points[points.length-1].value.toFixed(precision):' Â· needs more history'));
        });
        volume.setData(rows.filter(row=>row.volume>0).map(row=>({time:row.time,value:row.volume,color:row.close>=row.open?'rgba(16,185,129,.38)':'rgba(239,68,68,.38)'})));
        volume.applyOptions({visible:checked('volume')});
        levelLines.forEach(line=>primary.removePriceLine(line));levelLines=[];
        const line=(price,color,title)=>{
            if (!Number.isFinite(Number(price)) || Number(price)<=0) return;
            levelLines.push(primary.createPriceLine({price:Number(price),color,lineWidth:1,lineStyle:2,axisLabelVisible:true,title}));
        };
        // Live marks and historical closes are distinct observations.
        const isLive = payload.marketplace === 'live' || el.dataset.marketplace === 'live';
        primary.applyOptions({lastValueVisible:!isLive,priceLineVisible:!isLive});
        if(isLive) {

            line(payload.current_price,'#06b6d4',payload.quote_status && payload.quote_status!=='fresh' ? 'Stale mark' : 'Live mark');
        }
        // Shared live feed freshness: retain marks and show their real capture time.
        if(payload.source==='twelve_data_shared') {
            const live=payload.quote_status==='fresh';
            primary.applyOptions({lastValueVisible:false,priceLineVisible:false});
            if(!levelLines.length || !levelLines.some(l=>l.options().title==='Live mark'||l.options().title==='Stale mark'))
                line(payload.current_price,live?'#06b6d4':'#f59e0b',live?'Live mark':'Stale mark');
            legend.push((live?'Live feed':'Feed '+payload.quote_status)+' · '+payload.captured_at+' · '+payload.quote_age_seconds+'s old');
            modeButtons.forEach(b=>{b.disabled=false;b.title='';});
        }
        if(checked('levels')){line(payload.support,'#ef4444','Support');line(payload.resistance,'#10b981','Resistance');}
        let trading={};try{trading=JSON.parse(el.closest('[data-trade-workstation]')?.dataset.tradeChartData||'{}');}catch(_){}
        (trading.positions||[]).slice(0,50).forEach(position=>{
            if(checked('positions'))line(position.entry,position.direction==='short'?'#f97316':'#3b82f6',(position.direction==='short'?'Short':'Long')+' #'+position.id);
            if(checked('stop'))line(position.stop,'#ef4444','SL #'+position.id);
            if(checked('target'))line(position.target,'#10b981','TP #'+position.id);
        });
        markers.setMarkers(checked('executions')?executionMarkers(rows,trading.executions||[]):[]);
        const legendNode=wrapper?.querySelector('[data-chart-legend]');if(legendNode)legendNode.textContent=legend.join(' Â· ');
        chart.timeScale().applyOptions({timeVisible:['5m','15m','1h','4h'].includes(frame)});
        if(reset || !view){chart.priceScale('right').applyOptions({autoScale:true});chart.timeScale().fitContent();}
        else {restore(view);chart.priceScale('right').applyOptions({autoScale:currentAuto});if(checked('follow',false))chart.timeScale().scrollToRealTime();}
        const auto=controls?.querySelector('[data-chart-toggle="auto"]');if(auto)auto.checked=chart.priceScale('right').options().autoScale;
        timeButtons.forEach(button=>{
            button.disabled=(payload.timeframes?.[button.dataset.rcentzTimeframe]?.length||0)<2;
            button.classList.toggle('opacity-35',button.disabled);
            button.classList.toggle('bg-muted',button.dataset.rcentzTimeframe===frame);
            button.setAttribute('aria-pressed',String(button.dataset.rcentzTimeframe===frame));
        });
        modeButtons.forEach(button=>{button.classList.toggle('bg-muted',button.dataset.rcentzChartMode===mode);button.setAttribute('aria-pressed',String(button.dataset.rcentzChartMode===mode));});
    };
    const selectFrame=()=>payload.timeframes?.[frame]?.length>=2?frame:['1d','15m','5m','1h','4h','1w','1m','3m','1y'].find(key=>(payload.timeframes?.[key]?.length||0)>=2);
    frame=selectFrame()||frame;draw(true);
    timeButtons.forEach(button=>button.addEventListener('click',()=>{if(!button.disabled){frame=button.dataset.rcentzTimeframe;draw(true);}}));
    modeButtons.forEach(button=>button.addEventListener('click',()=>{mode=button.dataset.rcentzChartMode;localStorage.setItem('rcentz_stock_chart_mode',mode);draw(false,true);}));
    controls?.addEventListener('change',event=>{
        const input=event.target;
        if(input.matches('[data-chart-period]'))input.value=period(Number(input.dataset.chartPeriod));
        if(input.dataset.chartToggle==='auto')chart?.priceScale('right').applyOptions({autoScale:input.checked});
        draw();
    });
    wrapper?.querySelectorAll('[data-chart-action]').forEach(button=>button.addEventListener('click',async()=>{
        if(!chart)return;
        const action=button.dataset.chartAction;
        if(action==='reset'){draw(true);return;}
        if(action==='in'||action==='out'){
            const range=chart.timeScale().getVisibleLogicalRange();if(range)chart.timeScale().setVisibleLogicalRange(zoomRange(range,action==='in' ? 0.8 : 1.25));return;
        }
        if(action==='fullscreen'){
            const note=controls?.querySelector('[data-chart-note]');
            try{if(document.fullscreenElement===wrapper)await document.exitFullscreen();else await wrapper.requestFullscreen();}
            catch(_){if(note)note.textContent='Fullscreen is unavailable in this browser.';}
        }
    }));
    el.__rcentzRefreshAnalysis=next=>{
        payload=parseAnalysis(JSON.stringify(next||{}));el.dataset.analysis=JSON.stringify(next||{});
        const preferred=selectFrame();if(!preferred)return;const changed=preferred!==frame;frame=preferred;draw(changed);
    };
};


window.RcentzCharts = {
    mount(root = document) {
        for (const [node, chart] of RCENTZ_CHARTS) {
            if (!node.isConnected) { chart.remove(); RCENTZ_CHARTS.delete(node); }
        }
        for (const [selector, render] of [['[data-rcentz-candles]', renderCandles], ['[data-rcentz-sparkline]', renderSparkline], ['[data-rcentz-analysis]', renderAnalysis]]) {
            root.querySelectorAll(selector).forEach(node => { if (!RCENTZ_CHARTS.has(node)) render(node); });
        }
    },
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
