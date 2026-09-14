<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Purchase Confirmed - {{ site_name() }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        .content {
            background-color: #ffffff;
            padding: 30px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 20px;
        }
        h2 {
            color: #34495e;
            border-bottom: 2px solid #3498db;
            padding-bottom: 5px;
        }
        .button {
            display: inline-block;
            background-color: #3498db;
            color: white;
            padding: 12px 24px;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
            font-weight: bold;
        }
        .button:hover {
            background-color: #2980b9;
        }
        ul {
            padding-left: 20px;
        }
        li {
            margin-bottom: 8px;
        }
        .contact-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-top: 30px;
            border-left: 4px solid #3498db;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #dee2e6;
            color: #6c757d;
            font-size: 14px;
        }
        .transaction-details {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 5px 0;
        }
        .detail-label {
            font-weight: bold;
            color: #2c3e50;
        }
        .detail-value {
            color: #34495e;
        }
        .portfolio-value {
            background-color: #e8f5e8;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
            margin: 20px 0;
            border-left: 4px solid #27ae60;
        }
        .stock-info {
            background-color: #e3f2fd;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #2196f3;
        }
        .market-info {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #6c757d;
        }
        .next-steps {
            background-color: #e8f5e8;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #27ae60;
        }
        .risk-reminder {
            background-color: #fff3cd;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Stock Purchase Confirmed! 📊</h1>
    </div>
    
    <div class="content">
        <p>Hi {{ $user->name }},</p>
        
        <p>Your stock purchase has been successfully processed.</p>
        
        <h2>Transaction Details</h2>
        <div class="transaction-details">
            <div class="detail-row">
                <span class="detail-label">Transaction ID:</span>
                <span class="detail-value">{{ $transaction->id }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Stock Symbol:</span>
                <span class="detail-value">{{ $transaction->stock->symbol }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Company:</span>
                <span class="detail-value">{{ $transaction->stock->company_name }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Shares Purchased:</span>
                <span class="detail-value">{{ number_format($transaction->quantity, 2) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Price Per Share:</span>
                <span class="detail-value">{{ format_currency($transaction->price_per_share, 'USD', null, $user) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Total Amount:</span>
                <span class="detail-value">{{ format_currency($transaction->total_amount, 'USD', null, $user) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Purchase Date:</span>
                <span class="detail-value">{{ $transaction->created_at->format('M d, Y \a\t g:i A') }}</span>
            </div>
        </div>
        
        <h2>Stock Information</h2>
        <div class="stock-info">
            <div class="detail-row">
                <span class="detail-label">Sector:</span>
                <span class="detail-value">{{ $transaction->stock->sector ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Industry:</span>
                <span class="detail-value">{{ $transaction->stock->industry ?? 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Market Cap:</span>
                <span class="detail-value">{{ format_currency($transaction->stock->market_cap ?? 0, 'USD', null, $user) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Current Price:</span>
                <span class="detail-value">{{ format_currency($transaction->stock->current_price, 'USD', null, $user) }}</span>
            </div>
        </div>
        
        <h2>Portfolio Impact</h2>
        <div class="portfolio-value">
            <strong>Your total stock portfolio value is now: {{ format_currency($totalPortfolioValue, 'USD', null, $user) }}</strong>
        </div>
        
        <div style="text-align: center;">
            <a href="{{ route('trading.portfolio') }}" class="button">View Portfolio</a>
        </div>
        
        <h2>Market Information</h2>
        <div class="market-info">
            <div class="detail-row">
                <span class="detail-label">52-Week High:</span>
                <span class="detail-value">{{ format_currency($transaction->stock->fifty_two_week_high ?? 0, 'USD', null, $user) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">52-Week Low:</span>
                <span class="detail-value">{{ format_currency($transaction->stock->fifty_two_week_low ?? 0, 'USD', null, $user) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Volume:</span>
                <span class="detail-value">{{ number_format($transaction->stock->volume ?? 0) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">P/E Ratio:</span>
                <span class="detail-value">{{ $transaction->stock->pe_ratio ? number_format($transaction->stock->pe_ratio, 2) : 'N/A' }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Dividend Yield:</span>
                <span class="detail-value">{{ $transaction->stock->dividend_yield ? number_format($transaction->stock->dividend_yield, 2) . '%' : 'N/A' }}</span>
            </div>
        </div>
        
        <h2>Next Steps</h2>
        <div class="next-steps">
            <ol>
                <li><strong>Monitor Performance</strong>: Track your stock through the dashboard</li>
                <li><strong>Set Price Alerts</strong>: Configure notifications for price changes</li>
                <li><strong>Diversify</strong>: Consider adding more stocks to your portfolio</li>
                <li><strong>Research</strong>: Stay informed about company news and earnings</li>
            </ol>
        </div>
        
        <h2>Risk Reminder</h2>
        <div class="risk-reminder">
            <p>Stock investments carry inherent risks. Past performance does not guarantee future results. Please invest responsibly.</p>
        </div>
        
        <p>If you have any questions about your stock purchase, please contact our support team.</p>
        
        <p>Thanks,<br>
        {{ site_name() }} Team</p>
        
        <div class="contact-info">
            <strong>Contact Information:</strong><br>
            Email: {{ site_email() }}<br>
            Website: {{ site_url() }}
        </div>
    </div>
    
    <div class="footer">
        <p>&copy; {{ date('Y') }} {{ site_name() }}. All rights reserved.</p>
    </div>
</body>
</html>
