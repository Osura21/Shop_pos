<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Autosale.lk Seller Request Status</title>
</head>
<body style="margin:0;padding:0;background:#f4f6fb;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
    <div style="max-width:680px;margin:0 auto;padding:40px 20px;">
        <div style="background:#ffffff;border-radius:18px;overflow:hidden;box-shadow:0 10px 30px rgba(0,0,0,0.08);">
            <div style="background:linear-gradient(135deg,#332e78,#5c2d80);padding:28px 32px;color:#ffffff;">
                <div style="font-size:13px;font-weight:700;letter-spacing:0.08em;text-transform:uppercase;opacity:0.9;">
                    Autosale.lk
                </div>
                <h1 style="margin:12px 0 0;font-size:28px;line-height:1.2;">
                    {{ $heading }}
                </h1>
            </div>

            <div style="padding:32px;">
                <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">
                    Hello {{ $vendor->admin_name }},
                </p>

                <p style="margin:0 0 18px;font-size:15px;line-height:1.8;color:#4b5563;">
                    {{ $messageText }}
                </p>

                <div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:14px;padding:18px 20px;margin:0 0 24px;">
                    <p style="margin:0 0 10px;font-size:14px;"><strong>Shop Name:</strong> {{ $vendor->name }}</p>
                    <p style="margin:0 0 10px;font-size:14px;"><strong>Admin Name:</strong> {{ $vendor->admin_name }}</p>
                    <p style="margin:0 0 10px;font-size:14px;"><strong>Email:</strong> {{ $vendor->email }}</p>
                    <p style="margin:0 0 10px;font-size:14px;"><strong>Request Code:</strong> {{ $vendor->slug }}</p>
                    <p style="margin:0;font-size:14px;"><strong>Current Status:</strong> {{ ucfirst($vendor->status) }}</p>
                </div>

                @if($vendor->status === 'approved')
                    <div style="background:#effdf5;border:1px solid #bbf7d0;border-radius:14px;padding:18px 20px;margin:0 0 24px;">
                        <div style="font-size:13px;font-weight:700;color:#166534;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:10px;">
                            Approved Account Details
                        </div>

                        <p style="margin:0 0 10px;font-size:14px;line-height:1.8;color:#14532d;">
                            <strong>Domain Name:</strong> {{ $approvalData['domain_name'] ?? '-' }}
                        </p>

                        <p style="margin:0 0 10px;font-size:14px;line-height:1.8;color:#14532d;">
                            <strong>Current Theme:</strong> {{ $approvalData['theme_name'] ?? '-' }}
                        </p>

                        <p style="margin:0;font-size:14px;line-height:1.8;color:#14532d;">
                            <strong>Default Password:</strong> {{ $approvalData['admin_password'] ?? '-' }}
                        </p>
                    </div>
                @endif

                @if($vendor->status === 'rejected' && !empty($vendor->reason))
                    <div style="background:#fff1f2;border:1px solid #fecdd3;border-radius:14px;padding:18px 20px;margin:0 0 24px;">
                        <div style="font-size:13px;font-weight:700;color:#be123c;text-transform:uppercase;letter-spacing:0.06em;margin-bottom:8px;">
                            Rejection Reason
                        </div>
                        <div style="font-size:14px;line-height:1.8;color:#881337;">
                            {{ $vendor->reason }}
                        </div>
                    </div>
                @endif

                <div style="margin:28px 0;text-align:center;">
                    <a href="{{ $statusUrl }}"
                       style="display:inline-block;background:linear-gradient(135deg,#332e78,#5c2d80);color:#ffffff;text-decoration:none;padding:14px 22px;border-radius:12px;font-size:14px;font-weight:700;">
                        {{ $buttonText }}
                    </a>
                </div>

                <p style="margin:0 0 10px;font-size:14px;line-height:1.8;color:#6b7280;">
                    If the button does not work, copy and paste this link into your browser:
                </p>

                <p style="margin:0 0 20px;font-size:13px;line-height:1.8;word-break:break-all;color:#332e78;">
                    {{ $statusUrl }}
                </p>

                <p style="margin:0;font-size:14px;line-height:1.8;color:#4b5563;">
                    Thank you,<br>
                    <strong>Autosale.lk</strong>
                </p>
            </div>
        </div>
    </div>
</body>
</html>