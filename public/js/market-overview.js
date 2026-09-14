/**
 * Real-time market overview updates
 */
class MarketOverview {
    constructor() {
        this.echo = null;
        this.init();
    }

    /**
     * Initialize Echo and set up market listeners
     */
    init() {
        // Check if Echo is available
        if (typeof Echo === 'undefined') {
            console.warn('Laravel Echo not found. Market overview updates disabled.');
            return;
        }

        this.echo = Echo;
        this.setupMarketListeners();
    }

    /**
     * Set up market overview listeners
     */
    setupMarketListeners() {
        // Listen for market status changes
        this.echo.channel('market.status')
            .listen('.market.status.changed', (data) => {
                this.updateMarketStatus(data);
            });

        // Listen for market overview updates
        this.echo.channel('market.overview')
            .listen('.market.status.changed', (data) => {
                this.updateMarketOverview(data);
            });
    }

    /**
     * Update market status
     */
    updateMarketStatus(data) {
        const statusElement = document.querySelector('[data-market-status]');
        if (statusElement) {
            statusElement.textContent = data.status;
            statusElement.className = `inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${
                data.status === 'open' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
            }`;
        }
    }

    /**
     * Update market overview sections
     */
    updateMarketOverview(data) {
        // Update gainers section
        if (data.gainers && data.gainers.length > 0) {
            this.updateGainersSection(data.gainers);
        }

        // Update losers section
        if (data.losers && data.losers.length > 0) {
            this.updateLosersSection(data.losers);
        }

        // Update most active section
        if (data.most_active && data.most_active.length > 0) {
            this.updateMostActiveSection(data.most_active);
        }

        // Update last updated timestamp
        this.updateLastUpdated(data.updated_at);
    }

    /**
     * Update gainers section
     */
    updateGainersSection(gainers) {
        const container = document.querySelector('[data-market-section="gainers"]');
        if (!container) return;

        gainers.forEach((stock, index) => {
            const stockElement = container.querySelector(`[data-stock-index="${index}"]`);
            if (stockElement) {
                this.updateStockElement(stockElement, stock, true);
            }
        });
    }

    /**
     * Update losers section
     */
    updateLosersSection(losers) {
        const container = document.querySelector('[data-market-section="losers"]');
        if (!container) return;

        losers.forEach((stock, index) => {
            const stockElement = container.querySelector(`[data-stock-index="${index}"]`);
            if (stockElement) {
                this.updateStockElement(stockElement, stock, false);
            }
        });
    }

    /**
     * Update most active section
     */
    updateMostActiveSection(mostActive) {
        const container = document.querySelector('[data-market-section="most-active"]');
        if (!container) return;

        mostActive.forEach((stock, index) => {
            const stockElement = container.querySelector(`[data-stock-index="${index}"]`);
            if (stockElement) {
                this.updateStockElement(stockElement, stock, null);
            }
        });
    }

    /**
     * Update individual stock element
     */
    updateStockElement(element, stock, isGainer = null) {
        // Update symbol
        const symbolElement = element.querySelector('.stock-symbol');
        if (symbolElement) {
            symbolElement.textContent = stock.symbol;
        }

        // Update name
        const nameElement = element.querySelector('.stock-name');
        if (nameElement) {
            nameElement.textContent = stock.name;
        }

        // Update price
        const priceElement = element.querySelector('.stock-price');
        if (priceElement) {
            priceElement.textContent = this.formatPrice(stock.current_price);
        }

        // Update change percentage
        const changeElement = element.querySelector('.stock-change');
        if (changeElement) {
            const changePercent = parseFloat(stock.change_percentage);
            changeElement.textContent = this.formatChange(changePercent);
            
            // Set color based on whether it's a gainer or loser
            if (isGainer !== null) {
                changeElement.className = `text-sm font-medium ${
                    isGainer ? 'text-green-600' : 'text-red-600'
                }`;
            } else {
                changeElement.className = `text-sm font-medium ${
                    changePercent >= 0 ? 'text-green-600' : 'text-red-600'
                }`;
            }
        }

        // Update volume (for most active)
        const volumeElement = element.querySelector('.stock-volume');
        if (volumeElement && stock.volume) {
            volumeElement.textContent = this.formatVolume(stock.volume);
        }
    }

    /**
     * Update last updated timestamp
     */
    updateLastUpdated(timestamp) {
        const element = document.querySelector('[data-last-updated]');
        if (element) {
            const date = new Date(timestamp);
            element.textContent = date.toLocaleTimeString();
        }
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
     * Clean up
     */
    destroy() {
        // Echo will handle cleanup automatically
    }
}

// Initialize market overview when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.marketOverview = new MarketOverview();
});
