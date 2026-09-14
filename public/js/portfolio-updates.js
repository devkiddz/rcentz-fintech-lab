/**
 * Real-time portfolio updates
 */
class PortfolioUpdates {
    constructor() {
        this.echo = null;
        this.portfolioStocks = new Set();
        this.init();
    }

    /**
     * Initialize Echo and set up portfolio listeners
     */
    init() {
        // Check if Echo is available
        if (typeof Echo === 'undefined') {
            console.warn('Laravel Echo not found. Portfolio updates disabled.');
            return;
        }

        this.echo = Echo;
        this.setupPortfolioListeners();
        this.cachePortfolioStocks();
    }

    /**
     * Set up portfolio listeners
     */
    setupPortfolioListeners() {
        // Listen for stock price updates that affect portfolio
        this.echo.channel('portfolio.updates')
            .listen('.stock.price.updated', (data) => {
                this.updatePortfolioStock(data);
            });

        // Listen for individual stock updates
        this.portfolioStocks.forEach(symbol => {
            this.echo.channel(`stock.${symbol}`)
                .listen('.stock.price.updated', (data) => {
                    this.updatePortfolioStock(data);
                });
        });
    }

    /**
     * Cache portfolio stocks from the page
     */
    cachePortfolioStocks() {
        const portfolioElements = document.querySelectorAll('[data-portfolio-stock]');
        
        portfolioElements.forEach(element => {
            const symbol = element.dataset.portfolioStock;
            if (symbol) {
                this.portfolioStocks.add(symbol);
            }
        });
    }

    /**
     * Update portfolio stock data
     */
    updatePortfolioStock(data) {
        // Update individual stock in portfolio
        const stockElement = document.querySelector(`[data-portfolio-stock="${data.symbol}"]`);
        if (stockElement) {
            this.updateStockInPortfolio(stockElement, data);
        }

        // Update portfolio totals
        this.updatePortfolioTotals();
    }

    /**
     * Update individual stock in portfolio
     */
    updateStockInPortfolio(element, data) {
        // Update current price
        const priceElement = element.querySelector('.portfolio-stock-price');
        if (priceElement) {
            const currentPrice = parseFloat(data.current_price);
            const previousPrice = parseFloat(priceElement.dataset.previousPrice || currentPrice);
            
            priceElement.textContent = this.formatPrice(currentPrice);
            priceElement.dataset.previousPrice = currentPrice;

            // Add animation
            const changeClass = currentPrice > previousPrice ? 'price-up' : 'price-down';
            priceElement.classList.add(changeClass);
            
            setTimeout(() => {
                priceElement.classList.remove(changeClass);
            }, 1000);
        }

        // Update change percentage
        const changeElement = element.querySelector('.portfolio-stock-change');
        if (changeElement) {
            const changePercent = parseFloat(data.change_percent);
            changeElement.textContent = this.formatChange(changePercent);
            changeElement.className = `text-sm font-medium ${
                changePercent >= 0 ? 'text-green-600' : 'text-red-600'
            }`;
        }

        // Update market value
        const sharesElement = element.querySelector('.portfolio-stock-shares');
        const marketValueElement = element.querySelector('.portfolio-stock-value');
        
        if (sharesElement && marketValueElement) {
            const shares = parseFloat(sharesElement.textContent);
            const currentPrice = parseFloat(data.current_price);
            const marketValue = shares * currentPrice;
            
            marketValueElement.textContent = this.formatPrice(marketValue);
        }

        // Update gain/loss
        const costBasisElement = element.querySelector('.portfolio-stock-cost');
        const gainLossElement = element.querySelector('.portfolio-stock-gain-loss');
        
        if (costBasisElement && gainLossElement) {
            const costBasis = parseFloat(costBasisElement.textContent.replace(/[$,]/g, ''));
            const currentPrice = parseFloat(data.current_price);
            const shares = parseFloat(sharesElement.textContent);
            const gainLoss = (currentPrice - costBasis) * shares;
            
            gainLossElement.textContent = this.formatPrice(gainLoss);
            gainLossElement.className = `text-sm font-medium ${
                gainLoss >= 0 ? 'text-green-600' : 'text-red-600'
            }`;
        }
    }

    /**
     * Update portfolio totals
     */
    updatePortfolioTotals() {
        const portfolioStocks = document.querySelectorAll('[data-portfolio-stock]');
        let totalValue = 0;
        let totalCost = 0;
        let totalGainLoss = 0;

        portfolioStocks.forEach(stock => {
            const valueElement = stock.querySelector('.portfolio-stock-value');
            const costElement = stock.querySelector('.portfolio-stock-cost');
            const gainLossElement = stock.querySelector('.portfolio-stock-gain-loss');

            if (valueElement) {
                const value = parseFloat(valueElement.textContent.replace(/[$,]/g, ''));
                totalValue += value;
            }

            if (costElement) {
                const cost = parseFloat(costElement.textContent.replace(/[$,]/g, ''));
                totalCost += cost;
            }

            if (gainLossElement) {
                const gainLoss = parseFloat(gainLossElement.textContent.replace(/[$,]/g, ''));
                totalGainLoss += gainLoss;
            }
        });

        // Update total portfolio value
        const totalValueElement = document.querySelector('[data-portfolio-total-value]');
        if (totalValueElement) {
            totalValueElement.textContent = this.formatPrice(totalValue);
        }

        // Update total cost basis
        const totalCostElement = document.querySelector('[data-portfolio-total-cost]');
        if (totalCostElement) {
            totalCostElement.textContent = this.formatPrice(totalCost);
        }

        // Update total gain/loss
        const totalGainLossElement = document.querySelector('[data-portfolio-total-gain-loss]');
        if (totalGainLossElement) {
            totalGainLossElement.textContent = this.formatPrice(totalGainLoss);
            totalGainLossElement.className = `text-lg font-semibold ${
                totalGainLoss >= 0 ? 'text-green-600' : 'text-red-600'
            }`;
        }

        // Update total gain/loss percentage
        const totalGainLossPercentElement = document.querySelector('[data-portfolio-total-gain-loss-percent]');
        if (totalGainLossPercentElement && totalCost > 0) {
            const gainLossPercent = (totalGainLoss / totalCost) * 100;
            totalGainLossPercentElement.textContent = this.formatChange(gainLossPercent);
            totalGainLossPercentElement.className = `text-sm font-medium ${
                gainLossPercent >= 0 ? 'text-green-600' : 'text-red-600'
            }`;
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
     * Clean up
     */
    destroy() {
        this.portfolioStocks.clear();
    }
}

// Initialize portfolio updates when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    window.portfolioUpdates = new PortfolioUpdates();
});
