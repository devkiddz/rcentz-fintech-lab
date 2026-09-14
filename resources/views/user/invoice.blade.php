<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $purchase->id }} - Tesla Drives</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        :root {
            --black: #000000;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --white: #ffffff;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            line-height: 1.6;
            color: var(--black);
            max-width: 900px;
            margin: 0 auto;
            padding: 0;
            background-color: var(--white);
            font-weight: 300;
        }
        .hero {
            background: linear-gradient(135deg, var(--black), #0b0b0b, var(--black));
            color: var(--white);
            padding: 48px 40px 56px 40px;
            position: relative;
            overflow: hidden;
        }
        .hero .pattern {
            position: absolute; inset: 0; opacity: 0.12;
            background-image: radial-gradient(#ffffff 1px, transparent 1px);
            background-size: 24px 24px;
        }
        .hero-inner { position: relative; z-index: 1; display: flex; justify-content: space-between; align-items: flex-start; }
        .brand { display:flex; align-items:center; gap:12px; }
        .brand img { height: 16px; width: auto; filter: brightness(0) invert(1); }
        .brand .name { font-weight: 500; letter-spacing: -0.01em; }
        .invoice-meta { text-align: right; }
        .invoice-title { font-size: 28px; font-weight: 300; margin-bottom: 8px; letter-spacing: -0.01em; }
        .badge { display:inline-block; padding:6px 10px; border-radius: 999px; font-size: 12px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); }

        .container { padding: 32px 40px 48px 40px; }
        .grid { display:grid; grid-template-columns: 1fr 1fr; gap: 40px; }
        .section-title { font-size: 16px; font-weight: 500; color: var(--black); margin-bottom: 16px; letter-spacing: -0.01em; }
        .card { background: var(--white); border: 1px solid var(--gray-200); padding: 20px; }
        .muted { color: var(--gray-500); }
        .row { display:flex; justify-content: space-between; align-items:flex-start; margin-bottom: 10px; }
        .label { color: var(--gray-500); min-width: 130px; }
        .value { color: var(--black); text-align: right; flex:1; }

        .vehicle { background: var(--gray-50); border: 1px solid var(--gray-200); padding: 24px; margin-top: 24px; }
        .vehicle-grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; }

        .summary { background: var(--black); color: var(--white); padding: 28px; margin-top: 24px; }
        .summary .label { color: var(--gray-400); }
        .total { text-align:center; margin-top: 16px; padding: 16px; background: rgba(255,255,255,0.08); border: 1px solid rgba(255,255,255,0.15); }

        .foot { margin-top: 36px; padding-top: 20px; border-top: 1px solid var(--gray-200); text-align:center; color: var(--gray-500); font-size: 14px; }

        .note-crypto { background: #fef3c7; border: 1px solid #f59e0b; padding: 16px; margin-top: 16px; }
        .note-crypto .label { color: #92400e; }

        @media print {
            body { background-color: white; }
            .hero { color: var(--black); background: white; border-bottom: 1px solid var(--gray-200); }
            .hero .pattern { display:none; }
            .badge { background: #10b981; color: white; border: none; }
            .summary { color: var(--black); background: white; border: 1px solid var(--gray-200); }
            .summary .label { color: var(--gray-500); }
            .total { background: var(--gray-50); border-color: var(--gray-200); }
        }
    </style>
</head>
<body>
    <!-- Hero -->
    <div class="hero">
        <div class="pattern"></div>
        <div class="hero-inner">
            <div class="brand">
                <img src="{{ asset('tesla.svg') }}" alt="Tesla" />
            </div>
            <div class="invoice-meta">
                <div class="invoice-title">INVOICE</div>
                <div style="font-weight: 500; margin-bottom: 4px;">#{{ $purchase->id }}</div>
                <div class="muted" style="margin-bottom: 8px;">{{ $purchase->purchased_at->format('F d, Y') }}</div>
                <span class="badge">{{ strtoupper($purchase->status) }}</span>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Details Grid -->
        <div class="grid">
            <div class="card">
                <div class="section-title">Bill To</div>
                <div class="row"><div class="label">Customer</div><div class="value">{{ $purchase->billing_name ?: $purchase->user->name }}</div></div>
                <div class="row"><div class="label">Email</div><div class="value">{{ $purchase->billing_email ?: $purchase->user->email }}</div></div>
                @if($purchase->billing_phone)
                    <div class="row"><div class="label">Phone</div><div class="value">{{ $purchase->billing_phone }}</div></div>
                @endif
                @if($purchase->company_name)
                    <div class="row"><div class="label">Company</div><div class="value">{{ $purchase->company_name }}</div></div>
                @endif
                @if($purchase->full_billing_address)
                    <div class="row"><div class="label">Address</div><div class="value">{{ $purchase->full_billing_address }}</div></div>
                @endif
            </div>

            <div class="card">
                <div class="section-title">Purchase Details</div>
                <div class="row"><div class="label">Purchase ID</div><div class="value">#{{ $purchase->id }}</div></div>
                <div class="row"><div class="label">Date</div><div class="value">{{ $purchase->purchased_at->format('M d, Y g:i A') }}</div></div>
                <div class="row"><div class="label">Payment Method</div><div class="value">{{ $purchase->paymentMethod->name }}</div></div>
                @if($purchase->transaction_hash)
                    <div class="row"><div class="label">Transaction</div><div class="value" style="font-family: 'SF Mono', Monaco, monospace; font-size: 12px;">{{ \Illuminate\Support\Str::limit($purchase->transaction_hash, 24) }}{{ strlen($purchase->transaction_hash) > 24 ? '…' : '' }}</div></div>
                @endif
            </div>
        </div>

        <!-- Vehicle -->
        <div class="vehicle">
            <div class="section-title">Vehicle Details</div>
            <div class="vehicle-grid">
                <div class="row"><div class="label">Vehicle</div><div class="value">{{ $purchase->car->title }}</div></div>
                <div class="row"><div class="label">Year</div><div class="value">{{ $purchase->car->year }}</div></div>
                <div class="row"><div class="label">Make</div><div class="value">{{ $purchase->car->make }}</div></div>
                <div class="row"><div class="label">Model</div><div class="value">{{ $purchase->car->model }}</div></div>
                <div class="row"><div class="label">Color</div><div class="value">{{ $purchase->car->color }}</div></div>
                <div class="row"><div class="label">Engine</div><div class="value">{{ $purchase->car->engine }}</div></div>
                <div class="row"><div class="label">Transmission</div><div class="value">{{ $purchase->car->transmission }}</div></div>
                <div class="row"><div class="label">Original Price</div><div class="value">{{ currency_symbol() }}{{ number_format($purchase->car->price, 2) }}</div></div>
            </div>
        </div>

        <!-- Summary -->
        <div class="summary">
            <div class="section-title" style="color: #fff;">Payment Summary</div>
            <div class="row"><div class="label">Vehicle Price</div><div class="value">{{ currency_symbol() }}{{ number_format($purchase->amount, 2) }}</div></div>
            @if($purchase->isCryptoPurchase())
                <div class="note-crypto">
                    <div class="row"><div class="label">Crypto Amount</div><div class="value">{{ $purchase->formatted_crypto_amount }}</div></div>
                    @if($purchase->exchange_rate)
                        <div class="row"><div class="label">Exchange Rate</div><div class="value">1 {{ strtoupper($purchase->crypto_currency) }} = {{ currency_symbol() }}{{ number_format($purchase->exchange_rate, 2) }}</div></div>
                    @endif
                    @if(optional($purchase->paymentMethod)->network_fee)
                        <div class="row"><div class="label">Network Fee</div><div class="value">{{ $purchase->paymentMethod->formatted_network_fee }}</div></div>
                    @endif
                </div>
            @endif
            <div class="total">Total Paid: {{ currency_symbol() }}{{ number_format($purchase->amount, 2) }}</div>
        </div>

        <!-- Footer -->
        <div class="foot">
            <div style="font-weight: 500; color: #000; margin-bottom: 6px;">Tesla Drives</div>
            <div style="margin-bottom: 12px;">Premium Electric Vehicle Sales</div>
            <div style="margin-bottom: 12px;">Thank you for your business! This invoice serves as your official receipt.</div>
            <div style="margin-bottom: 16px;">For any questions regarding this invoice, please contact our support team.</div>
            <div style="font-size: 12px; color: var(--gray-400);">Generated on {{ now()->format('F d, Y \a\t g:i A') }}</div>
        </div>
    </div>
</body>
</html>
