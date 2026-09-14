<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC Application Approved - {{ site_name() }}</title>
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
        .application-details {
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
        .status-approved {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin: 20px 0;
            border-left: 4px solid #28a745;
        }
        .features-list {
            background-color: #e8f5e8;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #27ae60;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>KYC Application Approved! 🎉</h1>
    </div>
    
    <div class="content">
        <p>Hi {{ $user->name }},</p>
        
        <p>Great news! Your KYC (Know Your Customer) application has been <strong>approved</strong>.</p>
        
        <h2>Application Details</h2>
        <div class="application-details">
            <div class="detail-row">
                <span class="detail-label">Application ID:</span>
                <span class="detail-value">{{ $kyc->id }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Approval Date:</span>
                <span class="detail-value">{{ $kyc->updated_at->format('M d, Y \a\t g:i A') }}</span>
            </div>
            <div class="status-approved">
                <strong>Status: ✅ Approved</strong>
            </div>
        </div>
        
        <h2>What This Means</h2>
        <div class="features-list">
            <p>You now have full access to all trading and investment features on {{ site_name() }}:</p>
            <ul>
                <li><strong>Investment Trading</strong>: Buy and sell investment plans</li>
                <li><strong>Stock Trading</strong>: Trade stocks and securities</li>
                <li><strong>Withdrawals</strong>: Process withdrawals from your wallet</li>
                <li><strong>Advanced Features</strong>: Access to all platform features</li>
            </ul>
        </div>
        
        <div style="text-align: center;">
            <a href="{{ route('dashboard') }}" class="button">Start Trading</a>
        </div>
        
        <h2>Security Reminder</h2>
        <p>Please keep your account credentials secure and never share them with anyone.</p>
        
        <p>If you have any questions or need assistance, our support team is here to help.</p>
        
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
