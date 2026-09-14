<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice #{{ $purchase->id }} - {{ config('app.name', 'Tesla Drives') }}</title>
    <style type="text/css">
        @page { margin: 22px 26px; }
        body { font-family: sans-serif; color: #111; font-size: 11px; line-height: 1.45; }
        .container { width: 100%; }
        .row { width: 100%; }
        .left { float: left; }
        .right { float: right; }
        .clear { clear: both; }
        h1, h2, h3, h4 { margin: 0; padding: 0; }
        .muted { color: #666; }
        .small { font-size: 10px; }
        .tiny { font-size: 9px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .badge { display: inline-block; border: 1px solid #ddd; border-radius: 10px; padding: 2px 6px; font-size: 9px; }
        .section { margin-top: 10px; page-break-inside: avoid; }
        .card { border: 1px solid #e5e7eb; padding: 10px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { padding: 6px; vertical-align: top; }
        .table { border: 1px solid #e5e7eb; }
        .table th { background: #f5f5f5; font-weight: 600; }
        .table th, .table td { border-bottom: 1px solid #e5e7eb; }
        .no-border td, .no-border th { border: 0; padding: 2px 0; }
        .mono { font-family: monospace; font-size: 10px; }
        .divider { height: 1px; background: #e5e7eb; margin: 10px 0; }
        .title { font-size: 14px; font-weight: 600; }
        .header { padding-bottom: 8px; border-bottom: 1px solid #e5e7eb; }
    </style>
    </head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="row header">
            <div class="left">
                <div class="title">{{ config('app.name', 'Tesla Drives') }}</div>
                <div class="small muted">Official Invoice</div>
            </div>
            <div class="right text-right">
                <div class="title">INVOICE</div>
                <div>#{{ $purchase->id }}</div>
                <div class="small muted">{{ optional($purchase->purchased_at)->format('F d, Y') }}</div>
                <div class="badge">{{ strtoupper($purchase->status) }}</div>
            </div>
            <div class="clear"></div>
        </div>

        <!-- Bill To + Purchase Details -->
        <div class="row section">
            <table class="no-border">
                <tr>
                    <td style="width: 50%;">
                        <div class="card">
                            <div class="small muted" style="margin-bottom:4px;">Bill To</div>
                            <table class="no-border">
                                <tr>
                                    <td class="muted small" style="width: 35%;">Customer</td>
                                    <td class="small">{{ $purchase->billing_name ?: optional($purchase->user)->name }}</td>
                                </tr>
                                <tr>
                                    <td class="muted small">Email</td>
                                    <td class="small">{{ $purchase->billing_email ?: optional($purchase->user)->email }}</td>
                                </tr>
                                @if($purchase->billing_phone)
                                <tr>
                                    <td class="muted small">Phone</td>
                                    <td class="small">{{ $purchase->billing_phone }}</td>
                                </tr>
                                @endif
                                @if($purchase->company_name)
                                <tr>
                                    <td class="muted small">Company</td>
                                    <td class="small">{{ $purchase->company_name }}</td>
                                </tr>
                                @endif
                                @if($purchase->full_billing_address)
                                <tr>
                                    <td class="muted small">Address</td>
                                    <td class="small">{{ $purchase->full_billing_address }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </td>
                    <td style="width: 50%;">
                        <div class="card">
                            <div class="small muted" style="margin-bottom:4px;">Purchase</div>
                            <table class="no-border">
                                <tr>
                                    <td class="muted small" style="width: 40%;">Purchase ID</td>
                                    <td class="small">#{{ $purchase->id }}</td>
                                </tr>
                                <tr>
                                    <td class="muted small">Date</td>
                                    <td class="small">{{ optional($purchase->purchased_at)->format('M d, Y g:i A') }}</td>
                                </tr>
                                <tr>
                                    <td class="muted small">Payment</td>
                                    <td class="small">{{ optional($purchase->paymentMethod)->name ?: 'N/A' }}</td>
                                </tr>
                                @if($purchase->transaction_hash)
                                <tr>
                                    <td class="muted small">Transaction</td>
                                    <td class="mono">{{ strlen($purchase->transaction_hash) > 28 ? substr($purchase->transaction_hash,0,28).'…' : $purchase->transaction_hash }}</td>
                                </tr>
                                @endif
                            </table>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Vehicle Details -->
        <div class="row section">
            <div class="small muted" style="margin-bottom:6px;">Vehicle Details</div>
            <table class="table">
                <thead>
                    <tr>
                        <th style="text-align:left;">Vehicle</th>
                        <th style="text-align:left;">Spec</th>
                        <th style="text-align:left;">Original Price</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>
                            {{ optional($purchase->car)->title }}
                            @if(optional($purchase->car)->year)
                                <span class="muted">• {{ $purchase->car->year }}</span>
                            @endif
                        </td>
                        <td class="small">
                            {{ optional($purchase->car)->make }} {{ optional($purchase->car)->model }}
                            @if(optional($purchase->car)->color)
                                <span class="muted">• {{ $purchase->car->color }}</span>
                            @endif
                            @if(optional($purchase->car)->engine)
                                <span class="muted">• {{ $purchase->car->engine }}</span>
                            @endif
                            @if(optional($purchase->car)->transmission)
                                <span class="muted">• {{ $purchase->car->transmission }}</span>
                            @endif
                        </td>
                        <td>{{ currency_symbol() }}{{ number_format(optional($purchase->car)->price ?? 0, 2) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Payment Summary -->
        <div class="row section">
            <table class="no-border">
                <tr>
                    <td style="width: 60%; padding-right: 10px;">
                        <div class="card">
                            <div class="small muted" style="margin-bottom:6px;">Notes</div>
                            <div class="tiny muted">
                                Thank you for your purchase. This invoice serves as your official receipt. For questions, contact support.
                            </div>
                        </div>
                    </td>
                    <td style="width: 40%;">
                        <div class="card">
                            <table class="no-border" style="width:100%;">
                                <tr>
                                    <td class="muted small" style="width:60%;">Vehicle Price</td>
                                    <td class="text-right small">{{ currency_symbol() }}{{ number_format($purchase->amount, 2) }}</td>
                                </tr>
                                @if($purchase->isCryptoPurchase())
                                    <tr>
                                        <td class="muted small">Crypto Amount</td>
                                        <td class="text-right small">{{ $purchase->formatted_crypto_amount }}</td>
                                    </tr>
                                    @if($purchase->exchange_rate)
                                    <tr>
                                        <td class="muted small">Exchange Rate</td>
                                        <td class="text-right small">1 {{ strtoupper($purchase->crypto_currency) }} = {{ currency_symbol() }}{{ number_format($purchase->exchange_rate, 2) }}</td>
                                    </tr>
                                    @endif
                                    @if(optional($purchase->paymentMethod)->network_fee)
                                    <tr>
                                        <td class="muted small">Network Fee</td>
                                        <td class="text-right small">{{ $purchase->paymentMethod->formatted_network_fee }}</td>
                                    </tr>
                                    @endif
                                @endif
                                <tr>
                                    <td><div class="divider"></div></td>
                                    <td><div class="divider"></div></td>
                                </tr>
                                <tr>
                                    <td class="small" style="font-weight:600;">Total Paid</td>
                                    <td class="text-right" style="font-weight:600;">{{ currency_symbol() }}{{ number_format($purchase->amount, 2) }}</td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Footer -->
        <div class="row section">
            <div class="tiny muted text-center">
                Generated on {{ now()->format('F d, Y \a\t g:i A') }} • {{ config('app.name', 'Tesla Drives') }}
            </div>
        </div>
    </div>
</body>
</html>


