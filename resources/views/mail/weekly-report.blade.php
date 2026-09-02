<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Weekly AI Visibility Report</title>
</head>
<body style="margin:0;background:#f1f5f9;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;padding:24px 0;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;margin:0 auto;">
        <tr>
            <td style="background:#0b1220;border-radius:16px 16px 0 0;padding:24px 28px;">
                <div style="color:#ffffff;font-size:20px;font-weight:800;">{{ $digest['brand'] }}</div>
                <div style="color:#94a3b8;font-size:12px;margin-top:2px;">AI Visibility Report · {{ $digest['domain'] }} · {{ $digest['period'] }}</div>
            </td>
        </tr>
        <tr>
            <td style="background:#ffffff;border-radius:0 0 16px 16px;padding:28px;">
                @php
                    $thisRate = $digest['overall_this'];
                    $delta = $digest['overall_delta'];
                    $deltaLabel = $delta === null ? 'n/a' : ($delta > 0 ? '+' . $delta . '%' : ($delta < 0 ? $delta . '%' : '0%'));
                @endphp
                <div style="font-size:13px;color:#334155;line-height:1.7;">
                    <p>Hi {{ $digest['brand'] }}, here is how your store performed in AI answers this week.</p>
                </div>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:18px 0;">
                    <tr>
                        <td style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:16px 20px;text-align:center;">
                            <div style="font-size:11px;color:#64748b;text-transform:uppercase;letter-spacing:.5px;">Overall AI mention rate</div>
                            <div style="font-size:34px;font-weight:800;color:#0a84ff;">{{ $thisRate === null ? '—' : $thisRate . '%' }}</div>
                            <div style="font-size:12px;color:#16a34a;font-weight:600;">{{ $deltaLabel }} vs previous week</div>
                        </td>
                    </tr>
                </table>

                <div style="font-size:13px;font-weight:700;color:#0f172a;margin:20px 0 8px;">Per engine</div>
                @if (count($digest['engines']))
                    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                        @foreach ($digest['engines'] as $engine)
                            <tr>
                                <td style="padding:7px 0;font-size:13px;color:#475569;">{{ $engine['label'] }}</td>
                                <td style="padding:7px 0;font-size:13px;color:#0f172a;font-weight:700;text-align:right;">
                                    {{ $engine['this'] === null ? 'no data' : $engine['this'] . '%' }}
                                </td>
                                <td style="padding:7px 0;font-size:12px;text-align:right;color:
                                    {{ $engine['delta'] === null ? '#94a3b8' : ($engine['delta'] > 0 ? '#16a34a' : ($engine['delta'] < 0 ? '#dc2626' : '#94a3b8')) }};">
                                    {{ $engine['delta'] === null ? '·' : ($engine['delta'] > 0 ? '▲ +' . $engine['delta'] . '%' : ($engine['delta'] < 0 ? '▼ ' . $engine['delta'] . '%' : '—')) }}
                                </td>
                            </tr>
                        @endforeach
                    </table>
                @else
                    <div style="font-size:12px;color:#94a3b8;">Run your first checks to see per-engine rates.</div>
                @endif

                @if (count($digest['samples']))
                    <div style="font-size:13px;font-weight:700;color:#0f172a;margin:20px 0 8px;">Where AI mentioned you</div>
                    @foreach ($digest['samples'] as $sample)
                        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:10px 14px;margin-bottom:8px;font-size:12px;color:#334155;">
                            <strong>“{{ $sample['query'] }}”</strong><br>{{ $sample['snippet'] }}
                        </div>
                    @endforeach
                @endif

                @if ($digest['audit_score'] !== null)
                    <div style="font-size:13px;color:#475569;margin-top:14px;">
                        Latest AI Readiness Score: <strong>{{ $digest['audit_score'] }}/100</strong>
                        @if ($digest['audit_grade']) (Grade {{ $digest['audit_grade'] }}) @endif
                    </div>
                @endif

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:24px 0 6px;">
                    <tr>
                        <td style="background:#0a84ff;border-radius:10px;text-align:center;">
                            <a href="{{ url('/app') }}" style="display:inline-block;padding:12px 22px;color:#ffffff;font-size:13px;font-weight:700;text-decoration:none;">Open your dashboard →</a>
                        </td>
                    </tr>
                </table>
                <div style="font-size:10px;color:#94a3b8;text-align:center;margin-top:12px;">
                    AI Visibility · honest AI-visibility tracking for Indian D2C · <a href="{{ url('/unsubscribe') }}" style="color:#94a3b8;">Unsubscribe</a>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
