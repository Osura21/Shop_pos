<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bill #{{ $ticket->id }}</title>
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
        .mt-8 { margin-top: 8px; }
        .mt-12 { margin-top: 12px; }
        .mt-16 { margin-top: 16px; }

        .brand {
            font-size: 16px;
            font-weight: 800;
            line-height: 1.2;
        }

        .small {
            font-size: 11px;
            line-height: 1.4;
        }

        .title {
            font-size: 20px;
            font-weight: 900;
            margin: 8px 0 4px;
        }

        .order-no {
            font-size: 18px;
            font-weight: 900;
            margin: 6px 0;
        }

        .line {
            border-top: 2px solid #000;
            margin: 8px 0;
        }

        .thin-line {
            border-top: 1px solid #000;
            margin: 6px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            font-size: 11px;
            padding: 3px 0;
            vertical-align: top;
        }

        th {
            text-align: left;
            font-weight: 800;
        }

        td.right, th.right {
            text-align: right;
        }

        .meta-row td {
            font-size: 12px;
            padding: 2px 0;
            font-weight: 700;
        }

        .item-name {
            font-size: 12px;
            font-weight: 800;
        }

        .item-sub {
            font-size: 10px;
            margin-top: 2px;
            padding-left: 10px;
            line-height: 1.4;
        }

        .summary td {
            font-size: 12px;
            font-weight: 700;
            padding: 4px 0;
        }

        .summary .grand td {
            border-top: 2px solid #000;
            padding-top: 6px;
            font-size: 13px;
            font-weight: 900;
        }

        .footer {
            margin-top: 14px;
            text-align: center;
            font-size: 12px;
            font-weight: 700;
        }
    </style>
</head>
<body>
    @php
        $currency = \App\Models\TenantCurrencySetting::getCurrencySymbol($ticket->currency_code ?? 'LKR');
        $formatMoney = fn ($value) => $currency . ' ' . number_format((float) ($value ?? 0), 3);
    @endphp
    <div class="receipt">
        <div class="center brand">{{ $companyName }}</div>

        @if($addressLine)
            <div class="center small">{{ $addressLine }}</div>
        @endif

        @if($phone || $email)
            <div class="center small">
                {{ $phone ?: '' }}@if($phone && $email) | @endif{{ $email ?: '' }}
            </div>
        @endif

        <div class="line"></div>

        <div class="center title">BILL</div>
        <div class="center order-no">#{{ $ticket->id }}</div>

        <div class="thin-line"></div>

        <div class="center small"><strong>ORDER DATE</strong></div>
        <div class="center small"><strong>{{ optional($ticket->created_at)->format('Y-m-d h:i A') }}</strong></div>

        <div class="line"></div>

        <table class="meta-row">
            <tr>
                <td>ORDER TYPE :</td>
                <td class="right">{{ ucwords(str_replace('_', ' ', $ticket->channel ?: 'takeaway')) }}</td>
            </tr>
            <tr>
                <td>CUSTOMER :</td>
                <td class="right">{{ $customerName }}</td>
            </tr>
            @if($pmsGuestName || $pmsRoomLabel || $pmsBookingReference)
                <tr>
                    <td>PMS GUEST :</td>
                    <td class="right">{{ $pmsGuestName ?: '-' }}</td>
                </tr>
                <tr>
                    <td>ROOM :</td>
                    <td class="right">{{ $pmsRoomLabel ?: '-' }}</td>
                </tr>
                <tr>
                    <td>BOOKING :</td>
                    <td class="right">{{ $pmsBookingReference ?: '-' }}</td>
                </tr>
            @endif
        </table>

        <div class="line"></div>

        <div class="small"><strong>ITEMS</strong></div>

        <div class="line"></div>

        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="right">Qty</th>
                    <th class="right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($items as $item)
                    <tr>
                        <td class="item-name">{{ $item['product_name'] }}</td>
                        <td class="right item-name">{{ rtrim(rtrim(number_format($item['qty'], 3, '.', ''), '0'), '.') }}</td>
                        <td class="right item-name">{{ $formatMoney($item['line_total']) }}</td>
                    </tr>

                    <tr>
                        <td colspan="3" class="item-sub">
                            Unit: {{ $formatMoney($item['unit_price']) }}
                        </td>
                    </tr>

                    @foreach($item['options'] as $option)
                        <tr>
                            <td colspan="3" class="item-sub">
                                + {{ $option['label'] }} : {{ $option['value'] }}
                                @if($option['price'] > 0)
                                    ({{ $formatMoney($option['price']) }})
                                @endif
                            </td>
                        </tr>
                    @endforeach

                    @if($item['notes'])
                        <tr>
                            <td colspan="3" class="item-sub">
                                + Special Instructions : {{ $item['notes'] }}
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
        </table>

        <div class="mt-12 small"><strong>SUMMARY</strong></div>
        <div class="line"></div>

        <table class="summary">
            <tr>
                <td>Subtotal</td>
                <td class="right">{{ $formatMoney($subtotal) }}</td>
            </tr>
            <tr>
                <td>VAT 15%</td>
                <td class="right">{{ $formatMoney($taxTotal) }}</td>
            </tr>
            <tr class="grand">
                <td>Total</td>
                <td class="right">{{ $formatMoney($grandTotal) }}</td>
            </tr>
            <tr>
                <td>Due</td>
                <td class="right">{{ $formatMoney($dueAmount) }}</td>
            </tr>
        </table>

        <div class="line"></div>

        <div class="footer">Thank you for your visit</div>
    </div>

    @if($autoPrint)
        <script>
            window.addEventListener('load', function () {
                setTimeout(function () {
                    window.print();
                }, 250);
            });
        </script>
    @endif
</body>
</html>
