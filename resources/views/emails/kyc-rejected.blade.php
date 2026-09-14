<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC Application Update - {{ site_name() }}</title>
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
        .status-rejected {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
            margin: 20px 0;
            border-left: 4px solid #dc3545;
        }
        .rejection-reason {
            background-color: #fff3cd;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #ffc107;
        }
        .next-steps {
            background-color: #e8f5e8;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #27ae60;
        }
        .common-issues {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #6c757d;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>KYC Application Update</h1>
    </div>
    
    <div class="content">
        <p>Hi {{ $user->name }},</p>
        
        <p>Your KYC (Know Your Customer) application has been reviewed and requires additional information.</p>
        
        <h2>Application Details</h2>
        <div class="application-details">
            <div class="detail-row">
                <span class="detail-label">Application ID:</span>
                <span class="detail-value">{{ $kyc->id }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Review Date:</span>
                <span class="detail-value">{{ $kyc->updated_at->format('M d, Y \a\t g:i A') }}</span>
            </div>
            <div class="status-rejected">
                <strong>Status: ❌ Rejected</strong>
            </div>
        </div>
        
        <h2>Reason for Rejection</h2>
        <div class="rejection-reason">
            <p>{{ $kyc->rejection_reason ?? 'Your application did not meet our verification requirements.' }}</p>
        </div>
        
        <h2>Next Steps</h2>
        <div class="next-steps">
            <p>To resolve this issue, please:</p>
            <ol>
                <li><strong>Review the feedback</strong> provided above</li>
                <li><strong>Update your application</strong> with the required information</li>
                <li><strong>Resubmit your KYC</strong> application</li>
            </ol>
        </div>
        
        <div style="text-align: center;">
            <a href="{{ route('profile.kyc') }}" class="button">Update Application</a>
        </div>
        
        <h2>Common Issues</h2>
        <div class="common-issues">
            <ul>
                <li><strong>Document Quality</strong>: Ensure all documents are clear and legible</li>
                <li><strong>Information Accuracy</strong>: Verify all personal information is correct</li>
                <li><strong>Document Validity</strong>: Ensure documents are not expired</li>
                <li><strong>Address Verification</strong>: Provide current residential address</li>
            </ul>
        </div>
        
        <h2>Need Help?</h2>
        <p>If you need assistance or have questions about the rejection, please contact our support team.</p>
        
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
