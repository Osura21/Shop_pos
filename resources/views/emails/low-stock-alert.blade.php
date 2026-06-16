<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Low stock alert</title>
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <div style="max-width:760px;margin:0 auto;padding:36px 18px;">
        <div style="background:#ffffff;border:1px solid #fde8cc;border-radius:18px;overflow:hidden;box-shadow:0 18px 45px rgba(15,23,42,0.08);">
            <div style="background:linear-gradient(135deg,#f97316,#f59e0b);padding:28px 32px;color:#ffffff;">
                <div style="font-size:12px;font-weight:800;letter-spacing:0.09em;text-transform:uppercase;opacity:0.92;">
                    {{ $tenant_name }} inventory alert
                </div>
                <h1 style="margin:10px 0 0;font-size:28px;line-height:1.2;">
                    Low stock needs attention
                </h1>
                <p style="margin:10px 0 0;font-size:14px;line-height:1.6;opacity:0.92;">
                    Generated {{ $generated_at }}
                </p>
            </div>

            <div style="padding:28px 32px;">
                @if($triggered_ingredient)
                    <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:14px;padding:18px 20px;margin-bottom:22px;">
                        <div style="font-size:12px;font-weight:800;color:#c2410c;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:8px;">
                            Triggered by
                        </div>
                        <div style="font-size:18px;font-weight:800;color:#7c2d12;">
                            {{ $triggered_ingredient['name'] }}
                        </div>
                        <div style="font-size:14px;color:#9a3412;margin-top:6px;">
                            Current: <strong>{{ $triggered_ingredient['current_stock'] }} {{ $triggered_ingredient['unit'] }}</strong>
                            &nbsp; | &nbsp;
                            Alert level: <strong>{{ $triggered_ingredient['alert_quantity'] }} {{ $triggered_ingredient['unit'] }}</strong>
                        </div>
                    </div>
                @endif

                <h2 style="font-size:18px;margin:0 0 12px;color:#111827;">Low ingredients</h2>
                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-bottom:26px;">
                    <thead>
                        <tr>
                            <th align="left" style="background:#f8fafc;border-bottom:1px solid #e5e7eb;padding:10px;font-size:12px;color:#64748b;text-transform:uppercase;">Ingredient</th>
                            <th align="right" style="background:#f8fafc;border-bottom:1px solid #e5e7eb;padding:10px;font-size:12px;color:#64748b;text-transform:uppercase;">Current</th>
                            <th align="right" style="background:#f8fafc;border-bottom:1px solid #e5e7eb;padding:10px;font-size:12px;color:#64748b;text-transform:uppercase;">Alert</th>
                            <th align="right" style="background:#f8fafc;border-bottom:1px solid #e5e7eb;padding:10px;font-size:12px;color:#64748b;text-transform:uppercase;">Short</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($ingredients as $ingredient)
                            <tr>
                                <td style="border-bottom:1px solid #eef2f7;padding:12px 10px;font-size:14px;font-weight:700;color:#111827;">{{ $ingredient['name'] }}</td>
                                <td align="right" style="border-bottom:1px solid #eef2f7;padding:12px 10px;font-size:14px;">{{ $ingredient['current_stock'] }} {{ $ingredient['unit'] }}</td>
                                <td align="right" style="border-bottom:1px solid #eef2f7;padding:12px 10px;font-size:14px;">{{ $ingredient['alert_quantity'] }} {{ $ingredient['unit'] }}</td>
                                <td align="right" style="border-bottom:1px solid #eef2f7;padding:12px 10px;font-size:14px;color:#dc2626;font-weight:800;">{{ $ingredient['shortage'] }} {{ $ingredient['unit'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <h2 style="font-size:18px;margin:0 0 12px;color:#111827;">Affected menu items</h2>

                @forelse($affected_products as $product)
                    <div style="border:1px solid #e5e7eb;border-radius:14px;padding:16px 18px;margin-bottom:14px;">
                        <div style="display:flex;justify-content:space-between;gap:12px;align-items:flex-start;">
                            <div>
                                <div style="font-size:16px;font-weight:800;color:#111827;">{{ $product['name'] }}</div>
                                <div style="font-size:13px;color:#64748b;margin-top:4px;">
                                    Limited by {{ $product['limiting_ingredient'] }}
                                </div>
                            </div>
                            <div style="background:#fee2e2;color:#991b1b;border-radius:999px;padding:7px 12px;font-size:13px;font-weight:800;white-space:nowrap;">
                                {{ $product['can_make'] ?? 0 }} can be made
                            </div>
                        </div>

                        <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin-top:12px;">
                            <thead>
                                <tr>
                                    <th align="left" style="padding:8px 0;border-bottom:1px solid #eef2f7;font-size:11px;color:#64748b;text-transform:uppercase;">Ingredient</th>
                                    <th align="right" style="padding:8px 0;border-bottom:1px solid #eef2f7;font-size:11px;color:#64748b;text-transform:uppercase;">Needed</th>
                                    <th align="right" style="padding:8px 0;border-bottom:1px solid #eef2f7;font-size:11px;color:#64748b;text-transform:uppercase;">Current</th>
                                    <th align="right" style="padding:8px 0;border-bottom:1px solid #eef2f7;font-size:11px;color:#64748b;text-transform:uppercase;">Can Make</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($product['ingredients'] as $row)
                                    <tr>
                                        <td style="padding:8px 0;border-bottom:1px solid #f1f5f9;font-size:13px;{{ $row['is_low'] ? 'color:#b91c1c;font-weight:800;' : 'color:#334155;' }}">{{ $row['ingredient'] }}</td>
                                        <td align="right" style="padding:8px 0;border-bottom:1px solid #f1f5f9;font-size:13px;">{{ $row['required_per_product'] }} {{ $row['unit'] }}</td>
                                        <td align="right" style="padding:8px 0;border-bottom:1px solid #f1f5f9;font-size:13px;">{{ $row['current_stock'] }} {{ $row['unit'] }}</td>
                                        <td align="right" style="padding:8px 0;border-bottom:1px solid #f1f5f9;font-size:13px;font-weight:800;">{{ $row['can_make'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @empty
                    <div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:14px;padding:18px;color:#64748b;font-size:14px;">
                        No menu recipes are currently linked to these low-stock ingredients.
                    </div>
                @endforelse

                <p style="margin:24px 0 0;font-size:13px;line-height:1.7;color:#64748b;">
                    Update inventory or pause affected items before staff continue selling unavailable menu items.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
