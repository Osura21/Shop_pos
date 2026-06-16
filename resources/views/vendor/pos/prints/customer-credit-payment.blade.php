<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $payment->receipt_no }}</title>
    <style>
        @page {
            margin: 8px;
            size: 80mm auto;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #000;
            background: #fff;
        }

        .receipt {
            width: 78mm;
            margin: 0 auto;
            padding: 8px 6px 12px;
            box-sizing: border-box;
        }

        .center { text-align: center; }
        .line { border-top: 2px solid #000; margin: 8px 0; }
        .thin-line { border-top: 1px solid #000; margin: 6px 0; }
        .brand { font-size: 16px; font-weight: 800; line-height: 1.2; }
        .title { font-size: 18px; font-weight: 900; margin: 8px 0 4px; }
        .small { font-size: 11px; line-height: 1.4; }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        td {
            font-size: 12px;
            padding: 4px 0;
            vertical-align: top;
        }

        td.right {
            text-align: right;
            font-weight: 700;
        }
    </style>
</head>
<body @if(!empty($autoPrint)) onload="window.print()" @endif>
    @php
        $branch = $payment->branch;
        $currency = \App\Models\TenantCurrencySetting::getCurrencySymbol($payment->currency_code ?? 'LKR');
        $money = fn ($value) => $currency . ' ' . number_format((float) ($value ?? 0), 2, '.', ',');
    @endphp

    <div class="receipt">
        <div class="center brand">{{ $branch?->business_name ?: $branch?->name ?: 'Credit Receipt' }}</div>

        @if($branch?->phone || $branch?->email)
            <div class="center small">
                {{ $branch?->phone ?: '' }}@if($branch?->phone && $branch?->email) | @endif{{ $branch?->email ?: '' }}
            </div>
        @endif

        <div class="line"></div>

        <div class="center title">CREDIT PAYMENT</div>
        <div class="center small">{{ $payment->receipt_no }}</div>

        <div class="thin-line"></div>

        <table>
            <tr>
                <td>Customer</td>
                <td class="right">{{ $payment->customer?->name ?: 'Customer' }}</td>
            </tr>
            <tr>
                <td>Phone</td>
                <td class="right">{{ $payment->customer?->phone ?: '-' }}</td>
            </tr>
            <tr>
                <td>Date</td>
                <td class="right">{{ optional($payment->received_at)->format('Y-m-d h:i A') }}</td>
            </tr>
            <tr>
                <td>Method</td>
                <td class="right">{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td>
            </tr>
            <tr>
                <td>Register</td>
                <td class="right">{{ $payment->register?->name ?: '-' }}</td>
            </tr>
            @if($payment->reference)
                <tr>
                    <td>Reference</td>
                    <td class="right">{{ $payment->reference }}</td>
                </tr>
            @endif
        </table>

        <div class="line"></div>

        <table>
            <tr>
                <td><strong>Amount Received</strong></td>
                <td class="right"><strong>{{ $money($payment->amount) }}</strong></td>
            </tr>
        </table>

        @if($payment->notes)
            <div class="line"></div>
            <div class="small"><strong>Notes</strong></div>
            <div class="small">{{ $payment->notes }}</div>
        @endif

        <div class="line"></div>
        <div class="center small">Thank you</div>
    </div>
</body>
</html>
