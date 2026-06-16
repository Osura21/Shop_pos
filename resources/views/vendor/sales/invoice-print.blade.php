<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $invoice->invoice_no ?: 'Invoice' }}</title>

    <style>
        @page {
            margin: 24px 28px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 12px;
            color: #1f2937;
            background: #ffffff;
        }

        .invoice-wrapper {
            width: 100%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: top;
        }

        .company-name {
            font-size: 22px;
            font-weight: 800;
            color: #111827;
            margin-bottom: 4px;
        }

        .company-line {
            font-size: 11px;
            color: #6b7280;
            line-height: 1.55;
        }

        .invoice-title {
            font-size: 34px;
            font-weight: 900;
            color: #f59e0b;
            text-align: right;
            letter-spacing: 2px;
        }

        .invoice-subtitle {
            text-align: right;
            font-size: 11px;
            color: #6b7280;
            margin-top: 4px;
        }

        .divider {
            height: 1px;
            background: #e5e7eb;
            margin: 18px 0;
        }

        .meta-table td {
            width: 25%;
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .meta-label {
            display: block;
            font-size: 10px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
            font-weight: 700;
        }

        .meta-value {
            display: block;
            font-size: 12px;
            color: #111827;
            font-weight: 800;
        }

        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 10px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .badge-orange {
            background: #fff7ed;
            color: #ea580c;
            border: 1px solid #fed7aa;
        }

        .badge-green {
            background: #ecfdf5;
            color: #059669;
            border: 1px solid #a7f3d0;
        }

        .badge-blue {
            background: #eff6ff;
            color: #2563eb;
            border: 1px solid #bfdbfe;
        }

        .party-table {
            margin-top: 18px;
        }

        .party-table td {
            width: 50%;
            vertical-align: top;
            padding: 0;
        }

        .party-box {
            border: 1px solid #e5e7eb;
            padding: 14px;
            min-height: 118px;
        }

        .party-box-left {
            margin-right: 8px;
        }

        .party-box-right {
            margin-left: 8px;
        }

        .section-label {
            font-size: 10px;
            color: #f59e0b;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            font-weight: 900;
            margin-bottom: 8px;
        }

        .party-name {
            font-size: 14px;
            font-weight: 900;
            color: #111827;
            margin-bottom: 6px;
        }

        .party-line {
            font-size: 11px;
            color: #6b7280;
            line-height: 1.6;
        }

        .items-table {
            margin-top: 20px;
            border: 1px solid #e5e7eb;
        }

        .items-table th {
            background: #111827;
            color: #ffffff;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            padding: 10px 8px;
            border-right: 1px solid #374151;
            text-align: left;
        }

        .items-table th:last-child {
            border-right: none;
        }

        .items-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #e5e7eb;
            border-right: 1px solid #f3f4f6;
            vertical-align: top;
            font-size: 11px;
        }

        .items-table td:last-child {
            border-right: none;
        }

        .items-table tbody tr:nth-child(even) td {
            background: #fafafa;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .product-name {
            font-weight: 800;
            color: #111827;
            font-size: 11px;
        }

        .product-option {
            margin-top: 4px;
            font-size: 10px;
            color: #6b7280;
            line-height: 1.35;
        }

        .product-option strong {
            color: #374151;
            font-weight: 800;
        }

        .empty-row {
            text-align: center;
            color: #6b7280;
            padding: 20px !important;
        }

        .summary-layout {
            margin-top: 20px;
        }

        .summary-layout td {
            vertical-align: top;
        }

        .payment-box,
        .totals-box {
            border: 1px solid #e5e7eb;
            padding: 14px;
        }

        .payment-box {
            margin-right: 10px;
        }

        .totals-box {
            margin-left: 10px;
        }

        .summary-title {
            font-size: 13px;
            font-weight: 900;
            color: #111827;
            margin-bottom: 10px;
        }

        .summary-table td {
            padding: 7px 0;
            border-bottom: 1px dashed #e5e7eb;
            font-size: 11px;
        }

        .summary-table tr:last-child td {
            border-bottom: none;
        }

        .summary-label {
            color: #6b7280;
        }

        .summary-value {
            text-align: right;
            color: #111827;
            font-weight: 800;
        }

        .grand-total-row td {
            padding-top: 10px;
            font-size: 14px;
            font-weight: 900;
            color: #f59e0b;
        }

        .net-paid {
            color: #059669;
            font-weight: 900;
        }

        .footer-table {
            margin-top: 22px;
        }

        .thank-you {
            font-size: 13px;
            font-weight: 900;
            color: #111827;
        }

        .footer-note {
            font-size: 10px;
            color: #6b7280;
            margin-top: 4px;
            line-height: 1.4;
        }

        .uuid-line {
            margin-top: 14px;
            font-size: 9px;
            color: #9ca3af;
            word-break: break-all;
        }
    </style>
</head>

<body @if(!empty($autoPrint)) onload="window.print()" @endif>
@php
    $currencyCode = $invoice->currency_code ?: 'LKR';
    $currency = \App\Models\TenantCurrencySetting::getCurrencySymbol($currencyCode);

    $money = function ($value) {
        return number_format((float) ($value ?? 0), 3);
    };

    $qty = function ($value) {
        $number = (float) ($value ?? 0);

        return floor($number) == $number
            ? number_format($number, 0)
            : number_format($number, 3);
    };

    $pretty = function ($value) {
        return ucwords(str_replace('_', ' ', (string) ($value ?: '-')));
    };

    $pick = function ($source, array $keys, $default = '') {
        foreach ($keys as $key) {
            $value = data_get($source, $key);

            if (filled($value)) {
                return $value;
            }
        }

        return $default;
    };

    $normalizeOptions = function ($rawOptions) use ($pick) {
        if (empty($rawOptions)) {
            return [];
        }

        if (is_string($rawOptions)) {
            $decoded = json_decode($rawOptions, true);
            $rawOptions = is_array($decoded) ? $decoded : [];
        }

        if ($rawOptions instanceof \Illuminate\Support\Collection) {
            $rawOptions = $rawOptions->toArray();
        }

        if (!is_array($rawOptions)) {
            return [];
        }

        $rows = [];

        foreach ($rawOptions as $option) {
            $label = trim((string) $pick($option, [
                'label',
                'option_name',
                'name',
                'title',
                'group_name',
                'group',
                'option',
            ], ''));

            $value = trim((string) $pick($option, [
                'value',
                'value_label',
                'value_input',
                'option_value',
                'selected_value',
                'text',
                'display_value',
            ], ''));

            if ($label === '' && $value === '') {
                continue;
            }

            if ($value === '-' && $label === '') {
                continue;
            }

            $rows[] = [
                'label' => $label,
                'value' => $value,
            ];
        }

        return $rows;
    };

    $branch = $invoice->branch;
    $customer = $invoice->customer;

    $sellerName = $invoice->seller_name ?: ($branch->name ?? 'Company');

    // Important: prefer customer relation name first.
    $buyerName = filled($customer?->name)
        ? $customer->name
        : (filled($invoice->buyer_name) ? $invoice->buyer_name : 'Walk-In Customer');

    $pmsGuest = $invoice->pms_guest_snapshot ?: [];
    $pmsGuestName = data_get($pmsGuest, 'guest_name');
    $pmsRoomLabel = collect([
        data_get($pmsGuest, 'room_no'),
        data_get($pmsGuest, 'room_name'),
    ])->filter()->implode(' / ');
    $pmsBookingReference = data_get($pmsGuest, 'booking_reference') ?: $invoice->pms_booking_id;

    $issuedAt = $invoice->issued_at
        ? \Carbon\Carbon::parse($invoice->issued_at)->format('Y-m-d h:i A')
        : '-';
@endphp

<div class="invoice-wrapper">
    <table class="header-table">
        <tr>
            <td style="width: 58%;">
                <div class="company-name">{{ $sellerName }}</div>

                <div class="company-line">
                    @if($branch?->name)
                        {{ $branch->name }}<br>
                    @endif

                    @if($branch?->email)
                        Email: {{ $branch->email }}<br>
                    @endif

                    @if($branch?->phone)
                        Phone: {{ $branch->phone }}<br>
                    @endif
                </div>
            </td>

            <td style="width: 42%;">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-subtitle">
                    {{ $invoice->invoice_no ?: '-' }}
                </div>
            </td>
        </tr>
    </table>

    <div class="divider"></div>

    <table class="meta-table">
        <tr>
            <td>
                <span class="meta-label">Invoice Number</span>
                <span class="meta-value">{{ $invoice->invoice_no ?: '-' }}</span>
            </td>

            <td>
                <span class="meta-label">Invoice Date</span>
                <span class="meta-value">{{ $issuedAt }}</span>
            </td>

            <td>
                <span class="meta-label">Invoice Type</span>
                <span class="badge badge-orange">{{ $pretty($invoice->type) }}</span>
            </td>

            <td>
                <span class="meta-label">Purpose</span>
                <span class="badge badge-green">{{ $pretty($invoice->purpose) }}</span>
            </td>
        </tr>

        <tr>
            <td>
                <span class="meta-label">Status</span>
                <span class="badge badge-green">{{ $pretty($invoice->status) }}</span>
            </td>

            <td>
                <span class="meta-label">Kind</span>
                <span class="badge badge-blue">{{ $pretty($invoice->kind) }}</span>
            </td>

            <td>
                <span class="meta-label">Currency</span>
                <span class="meta-value">{{ $currency }}</span>
            </td>

            <td>
                <span class="meta-label">Payment</span>
                <span class="meta-value">
                    {{ ((float) ($invoice->net_paid ?? 0) >= (float) ($invoice->total ?? 0)) ? 'Paid' : 'Pending' }}
                </span>
            </td>
        </tr>
    </table>

    <table class="party-table">
        <tr>
            <td>
                <div class="party-box party-box-left">
                    <div class="section-label">Seller</div>
                    <div class="party-name">{{ $sellerName }}</div>

                    <div class="party-line">
                        @if($branch?->name)
                            {{ $branch->name }}<br>
                        @endif

                        @if($branch?->email)
                            {{ $branch->email }}<br>
                        @endif

                        @if($branch?->phone)
                            {{ $branch->phone }}<br>
                        @endif

                        @if(!$branch?->name && !$branch?->email && !$branch?->phone)
                            -
                        @endif
                    </div>
                </div>
            </td>

            <td>
                <div class="party-box party-box-right">
                    <div class="section-label">Bill To</div>
                    <div class="party-name">{{ $buyerName }}</div>

                    <div class="party-line">
                        @if($customer?->email)
                            {{ $customer->email }}<br>
                        @endif

                        @if($customer?->phone)
                            {{ $customer->phone }}<br>
                        @endif

                        @if(!$customer?->email && !$customer?->phone)
                            Walk-In Customer
                        @endif

                        @if($pmsGuestName || $pmsRoomLabel || $pmsBookingReference)
                            <br>
                            PMS Guest: {{ $pmsGuestName ?: '-' }}<br>
                            Room: {{ $pmsRoomLabel ?: '-' }}<br>
                            Booking: {{ $pmsBookingReference ?: '-' }}
                        @endif
                    </div>
                </div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;" class="text-center">#</th>
                <th style="width: 31%;">Description</th>
                <th style="width: 13%;" class="text-right">Unit Price</th>
                <th style="width: 10%;" class="text-center">Qty</th>
                <th style="width: 13%;" class="text-right">Sub Total</th>
                <th style="width: 13%;" class="text-right">Tax</th>
                <th style="width: 15%;" class="text-right">Total</th>
            </tr>
        </thead>

        <tbody>
            @forelse($invoice->items as $index => $item)
                @php
                    $itemSubtotal = $item->subtotal ?? $item->line_subtotal ?? 0;
                    $itemTotal = $item->line_total ?? $item->total ?? 0;
                    $itemQty = $item->qty ?? $item->quantity ?? 0;
                    $optionRows = $normalizeOptions($item->options ?? []);
                @endphp

                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>

                    <td>
                        <div class="product-name">
                            {{ $item->product_name ?? $item->name ?? $item->description ?? '-' }}
                        </div>

                        @foreach($optionRows as $optionRow)
                            <div class="product-option">
                                @if($optionRow['label'] !== '' && $optionRow['value'] !== '')
                                    <strong>{{ $optionRow['label'] }}:</strong> {{ $optionRow['value'] }}
                                @elseif($optionRow['label'] !== '')
                                    {{ $optionRow['label'] }}
                                @else
                                    {{ $optionRow['value'] }}
                                @endif
                            </div>
                        @endforeach
                    </td>

                    <td class="text-right">{{ $currency }} {{ $money($item->unit_price ?? 0) }}</td>
                    <td class="text-center">{{ $qty($itemQty) }}</td>
                    <td class="text-right">{{ $currency }} {{ $money($itemSubtotal) }}</td>
                    <td class="text-right">{{ $currency }} {{ $money($item->tax_total ?? 0) }}</td>
                    <td class="text-right"><strong>{{ $currency }} {{ $money($itemTotal) }}</strong></td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty-row">No invoice items available.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="summary-layout">
        <tr>
            <td style="width: 50%;">
                <div class="payment-box">
                    <div class="summary-title">Payment Summary</div>

                    <table class="summary-table">
                        <tr>
                            <td class="summary-label">Paid Amount</td>
                            <td class="summary-value">{{ $currency }} {{ $money($invoice->paid_amount ?? 0) }}</td>
                        </tr>

                        <tr>
                            <td class="summary-label">Refunded Amount</td>
                            <td class="summary-value">{{ $currency }} {{ $money($invoice->refunded_amount ?? 0) }}</td>
                        </tr>

                        <tr>
                            <td class="summary-label">Net Paid</td>
                            <td class="summary-value net-paid">{{ $currency }} {{ $money($invoice->net_paid ?? 0) }}</td>
                        </tr>
                    </table>
                </div>
            </td>

            <td style="width: 50%;">
                <div class="totals-box">
                    <div class="summary-title">Invoice Summary</div>

                    <table class="summary-table">
                        <tr>
                            <td class="summary-label">Sub Total</td>
                            <td class="summary-value">{{ $currency }} {{ $money($invoice->subtotal ?? 0) }}</td>
                        </tr>

                        <tr>
                            <td class="summary-label">Tax Total</td>
                            <td class="summary-value">{{ $currency }} {{ $money($invoice->tax_total ?? 0) }}</td>
                        </tr>

                        @if((float) ($invoice->discount_total ?? 0) > 0)
                            <tr>
                                <td class="summary-label">Discount</td>
                                <td class="summary-value">- {{ $currency }} {{ $money($invoice->discount_total ?? 0) }}</td>
                            </tr>
                        @endif

                        <tr class="grand-total-row">
                            <td>Total</td>
                            <td class="summary-value">{{ $currency }} {{ $money($invoice->total ?? 0) }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table class="footer-table">
        <tr>
            <td>
                <div class="thank-you">Thank you.</div>
                <div class="footer-note">
                    This invoice was generated electronically.
                </div>
            </td>
        </tr>
    </table>

    <div class="uuid-line">
        UUID: {{ $invoice->uuid ?: '-' }}
    </div>
</div>
</body>
</html>
