<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Investment Purchase Confirmed - {{ site_name() }}</title>
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
    </style>
</head>
<body>
    <div class="header">
        <h1>Investment Purchase Confirmed! 📈</h1>
    </div>
    
    <div class="content">
        <p>Hi {{ $user->name }},</p>
        
        <p>Your investment purchase has been successfully processed.</p>
        
        <h2>Transaction Details</h2>
        <div class="transaction-details">
            <div class="detail-row">
                <span class="detail-label">Transaction ID:</span>
                <span class="detail-value">{{ $transaction->id }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Investment Plan:</span>
                <span class="detail-value">{{ $transaction->investmentPlan->name }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Units Purchased:</span>
                <span class="detail-value">{{ number_format($transaction->units, 2) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Total Amount:</span>
                <span class="detail-value">{{ format_currency($transaction->total_amount, 'USD', null, $user) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Purchase Date:</span>
                <span class="detail-value">{{ $transaction->created_at->format('M d, Y \a\t g:i A') }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">NAV at Purchase:</span>
                <span class="detail-value">{{ format_currency($transaction->nav_at_transaction, 'USD', null, $user) }}</span>
            </div>
        </div>
        
        <h2>Investment Summary</h2>
        <div class="transaction-details">
            <div class="detail-row">
                <span class="detail-label">Plan Type:</span>
                <span class="detail-value">{{ ucfirst(str_replace('_', ' ', $transaction->investmentPlan->type)) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Risk Level:</span>
                <span class="detail-value">{{ ucfirst($transaction->investmentPlan->risk_level) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Management Fee:</span>
                <span class="detail-value">{{ number_format($transaction->investmentPlan->management_fee * 100, 2) }}%</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Expense Ratio:</span>
                <span class="detail-value">{{ number_format($transaction->investmentPlan->expense_ratio * 100, 2) }}%</span>
            </div>
        </div>
        
        <h2>Portfolio Impact</h2>
        <div class="portfolio-value">
            <strong>Your total investment portfolio value is now: {{ format_currency($totalPortfolioValue, 'USD', null, $user) }}</strong>
        </div>
        
        <div style="text-align: center;">
            <a href="{{ route('portfolio.index') }}" class="button">View Portfolio</a>
        </div>
        
        <h2>Important Information</h2>
        <div class="transaction-details">
            <div class="detail-row">
                <span class="detail-label">Minimum Investment:</span>
                <span class="detail-value">{{ format_currency($transaction->investmentPlan->minimum_investment, 'USD', null, $user) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Total Assets:</span>
                <span class="detail-value">{{ format_currency($transaction->investmentPlan->total_assets ?? 0, 'USD', null, $user) }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Dividend Yield:</span>
                <span class="detail-value">{{ $transaction->investmentPlan->dividend_yield ? number_format($transaction->investmentPlan->dividend_yield, 2) . '%' : 'N/A' }}</span>
            </div>
        </div>
        
        <h2>Next Steps</h2>
        <ol>
            <li><strong>Monitor Performance</strong>: Track your investment through the dashboard</li>
            <li><strong>Set Alerts</strong>: Configure notifications for price changes</li>
            <li><strong>Diversify</strong>: Consider adding more investments to your portfolio</li>
        </ol>
        
        <p>If you have any questions about your investment, please contact our support team.</p>
        
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
