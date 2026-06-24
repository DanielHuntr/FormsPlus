<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $formTitle }}</title>
</head>
<body style="margin:0;padding:0;background:#f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:#18181b">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f5;padding:40px 16px">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.08)">

                    <!-- Header -->
                    <tr>
                        <td style="background:#18181b;padding:24px 32px">
                            <p style="margin:0;color:#a1a1aa;font-size:13px;text-transform:uppercase;letter-spacing:.08em">Forms Plus</p>
                            <h1 style="margin:4px 0 0;color:#ffffff;font-size:20px;font-weight:600">{{ $formTitle }}</h1>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding:32px">
                            <p style="margin:0 0 20px;color:#71717a;font-size:14px">Received {{ $submittedAt }}</p>

                            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;border-radius:6px;overflow:hidden;border:1px solid #e4e4e7">
                                @foreach ($data as $key => $value)
                                    @php
                                        $displayValue = is_array($value) ? implode(', ', $value) : (string) $value;
                                        $label = ucwords(str_replace('_', ' ', $key));
                                    @endphp
                                    <tr>
                                        <td style="padding:10px 16px;background:#fafafa;font-size:13px;font-weight:600;color:#71717a;width:160px;border-bottom:1px solid #e4e4e7;vertical-align:top;white-space:nowrap">
                                            {{ $label }}
                                        </td>
                                        <td style="padding:10px 16px;font-size:14px;color:#18181b;border-bottom:1px solid #e4e4e7;vertical-align:top;word-break:break-word">
                                            {{ $displayValue ?: '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="padding:16px 32px 24px;border-top:1px solid #f4f4f5">
                            <p style="margin:0;color:#a1a1aa;font-size:12px">Sent by Forms Plus on {{ config('app.name') }}</p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
