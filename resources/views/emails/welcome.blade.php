<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ site_name() }}</title>
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
    </style>
</head>
<body>
    <div class="header">
        <h1>Welcome to {{ site_name() }}!</h1>
    </div>
    
    <div class="content">
        <p>Hi {{ $user->name }},</p>
        
        <p>Welcome to {{ site_name() }}! We're excited to have you on board.</p>
        
        <h2>Getting Started</h2>
        <p>Here are a few things you can do to get started:</p>
        <ul>
            <li><strong>Complete Your Profile</strong>: Update your personal information and preferences</li>
            <li><strong>Browse Investments</strong>: Explore our investment opportunities</li>
            <li><strong>Check Out Stocks</strong>: View available stocks for trading</li>
            <li><strong>Set Up Your Wallet</strong>: Add funds to start investing</li>
        </ul>
        
        <h2>Next Steps</h2>
        <ol>
            <li><strong>Verify Your Email</strong>: Please verify your email address to access all features</li>
            <li><strong>Complete KYC</strong>: If required, complete your KYC verification</li>
            <li><strong>Fund Your Wallet</strong>: Add funds to start investing and trading</li>
        </ol>
        
        <div style="text-align: center;">
            <a href="{{ route('dashboard') }}" class="button">Go to Dashboard</a>
        </div>
        
        <p>If you have any questions, feel free to contact our support team.</p>
        
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
