/**
 * Real-time stock price updates using Laravel Echo
 */
class StockUpdates {
    constructor() {
        this.echo = null;
        this.subscriptions = new Map();
        this.priceElements = new Map();
        this.init();
    }

    /**
     * Initialize Echo and set up event listeners
     */
    init() {
        // Check if Echo is available
        if (typeof Echo === 'undefined') {
            console.warn('Laravel Echo not found. Real-time updates disabled.');
            return;
        }

        this.echo = Echo;
        this.setupGlobalListeners();
        this.setupStockListeners();
    }

    /**
     * Set up global market listeners
     */
    setupGlobalListeners() {
        // Listen for market overview updates
        this.echo.channel('market.overview')
            .listen('.market.status.changed', (data) => {
                this.updateMarketOverview(data);
            });

        // Listen for stock quotes updates
        this.echo.channel('stock.quotes')
            .listen('.stock.quote.changed', (data) => {
                this.updateStockQuote(data);
            });
    }

    /**
     * Set up individual stock listeners
     */
    setupStockListeners() {
        // Get all stock symbols from the page
        const stockElements = document.querySelectorAll('[data-stock-symbol]');
        
        stockElements.forEach(element => {
            const symbol = element.dataset.stockSymbol;
            if (symbol) {
                this.subscribeToStock(symbol);
                this.cachePriceElement(symbol, element);
            }
        });
    }

    /**
     * Subscribe to a specific stock channel
     */
    subscribeToStock(symbol) {
        if (this.subscriptions.has(symbol)) {
            return; // Already subscribed
        }

        const channel = this.echo.channel(`stock.${symbol}`);
        
        channel.listen('.stock.price.updated', (data) => {
            this.updateStockPrice(data);
        });

        channel.listen('.stock.quote.changed', (data) => {
            this.updateStockQuote(data);
        });

        this.subscriptions.set(symbol, channel);
    }

    /**
     * Cache price element for quick updates
     */
    cachePriceElement(symbol, element) {
        this.priceElements.set(symbol, element);
    }

    /**
     * Update stock price with animation
     */
    updateStockPrice(data) {
        const element = this.priceElements.get(data.symbol);
        if (!element) return;

        const currentPrice = parseFloat(data.current_price);
        const previousPrice = parseFloat(element.dataset.previousPrice || currentPrice);
        
        // Update the price
        element.textContent = this.formatPrice(currentPrice);
        element.dataset.previousPrice = currentPrice;

        // Add animation class
        const changeClass = currentPrice > previousPrice ? 'price-up' : 'price-down';
        element.classList.add(changeClass);
        
        // Remove animation class after animation completes
        setTimeout(() => {
            element.classList.remove(changeClass);
        }, 1000);

        // Update change percentage if element exists
        const changeElement = document.querySelector(`[data-stock-change="${data.symbol}"]`);
        if (changeElement) {
            const changePercent = parseFloat(data.change_percent);
            changeElement.textContent = this.formatChange(changePercent);
            changeElement.className = `text-sm font-medium ${changePercent >= 0 ? 'text-green-600' : 'text-red-600'}`;
        }
    }

    /**
     * Update stock quote data
     */
    updateStockQuote(data) {
        // Update volume if element exists
        const volumeElement = document.querySelector(`[data-stock-volume="${data.symbol}"]`);
        if (volumeElement) {
            volumeElement.textContent = this.formatVolume(data.volume);
        }

        // Update high/low if elements exist
        const highElement = document.querySelector(`[data-stock-high="${data.symbol}"]`);
        if (highElement) {
            highElement.textContent = this.formatPrice(data.high);
        }

        const lowElement = document.querySelector(`[data-stock-low="${data.symbol}"]`);
        if (lowElement) {
            lowElement.textContent = this.formatPrice(data.low);
        }
    }

    /**
     * Update market overview
     */
    updateMarketOverview(data) {
        // Update gainers
        if (data.gainers && data.gainers.length > 0) {
            this.updateMarketSection('gainers', data.gainers);
        }

        // Update losers
        if (data.losers && data.losers.length > 0) {
            this.updateMarketSection('losers', data.losers);
        }

        // Update most active
        if (data.most_active && data.most_active.length > 0) {
            this.updateMarketSection('most-active', data.most_active);
        }
    }

    /**
     * Update market section (gainers, losers, most active)
     */
    updateMarketSection(sectionType, stocks) {
        const container = document.querySelector(`[data-market-section="${sectionType}"]`);
        if (!container) return;

        stocks.forEach((stock, index) => {
            const stockElement = container.querySelector(`[data-stock-index="${index}"]`);
            if (stockElement) {
                const priceElement = stockElement.querySelector('.stock-price');
                const changeElement = stockElement.querySelector('.stock-change');
                
                if (priceElement) {
                    priceElement.textContent = this.formatPrice(stock.current_price);
                }
                
                if (changeElement) {
                    const changePercent = parseFloat(stock.change_percentage);
                    changeElement.textContent = this.formatChange(changePercent);
                    changeElement.className = `text-sm font-medium ${changePercent >= 0 ? 'text-green-600' : 'text-red-600'}`;
                }
            }
        });
    }

    /**
     * Format price for display
     */
    formatPrice(price) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        }).format(price);
    }

    /**
     * Format change percentage
     */
    formatChange(change) {
        const sign = change >= 0 ? '+' : '';
        return `${sign}${change.toFixed(2)}%`;
    }

    /**
     * Format volume
     */
    formatVolume(volume) {
        if (!volume) return 'N/A';
        
        if (volume >= 1000000000) {
            return `${(volume / 1000000000).toFixed(2)}B`;
        } else if (volume >= 1000000) {
            return `${(volume / 1000000).toFixed(2)}M`;
        } else if (volume >= 1000) {
            return `${(volume / 1000).toFixed(2)}K`;
        }
        
        return volume.toLocaleString();
    }

    /**
     * Clean up subscriptions
     */
    destroy() {
        this.subscriptions.forEach((channel, symbol) => {
            channel.unsubscribe();
        });
        this.subscriptions.clear();
        this.priceElements.clear();
    }
}

// Initialize stock updates when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.stockUpdates = new StockUpdates();
});
