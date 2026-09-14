<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KYC Application Submitted - {{ site_name() }}</title>
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
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            text-align: center;
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
        .required-documents {
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
        <h1>KYC Application Submitted</h1>
    </div>
    
    <div class="content">
        <p>Hi {{ $user->name }},</p>
        
        <p>Your KYC (Know Your Customer) application has been successfully submitted and is now under review.</p>
        
        <h2>Application Details</h2>
        <div class="application-details">
            <div class="detail-row">
                <span class="detail-label">Application ID:</span>
                <span class="detail-value">{{ $kyc->id }}</span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Submission Date:</span>
                <span class="detail-value">{{ $kyc->created_at->format('M d, Y \a\t g:i A') }}</span>
            </div>
            <div class="status-pending">
                <strong>Status: Pending Review</strong>
            </div>
        </div>
        
        <h2>What Happens Next?</h2>
        <div class="next-steps">
            <p>Our team will review your application within 1-3 business days. You'll receive an email notification once the review is complete.</p>
        </div>
        
        <h2>Required Documents</h2>
        <div class="required-documents">
            <p>Please ensure you have submitted:</p>
            <ul>
                <li>Valid government-issued ID</li>
                <li>Proof of address</li>
                <li>Additional documents if requested</li>
            </ul>
        </div>
        
        <div style="text-align: center;">
            <a href="{{ route('profile.kyc') }}" class="button">View Application Status</a>
        </div>
        
        <p>If you need to update any information or have questions, please contact our support team.</p>
        
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
