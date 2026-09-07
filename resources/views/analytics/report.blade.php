<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $report['report']['title'] ?? 'AI Visibility Report' }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, sans-serif; background: #f8fafc; color: #1e293b; padding: 2rem; }
        .container { max-width: 800px; margin: 0 auto; }
        .header { text-align: center; margin-bottom: 2rem; padding-bottom: 1rem; border-bottom: 2px solid #e2e8f0; }
        .header h1 { font-size: 1.5rem; font-weight: 800; color: #0f172a; }
        .header p { color: #64748b; font-size: 0.875rem; margin-top: 0.5rem; }
        .section { margin-bottom: 2rem; }
        .section h2 { font-size: 1.125rem; font-weight: 700; color: #0f172a; margin-bottom: 1rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0; }
        .grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; }
        .card { background: white; border: 1px solid #e2e8f0; border-radius: 0.75rem; padding: 1.5rem; text-align: center; }
        .card-label { font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
        .card-value { font-size: 2rem; font-weight: 800; margin-top: 0.5rem; }
        .card-change { font-size: 0.75rem; margin-top: 0.25rem; }
        .text-emerald { color: #059669; }
        .text-red { color: #dc2626; }
        .text-slate { color: #64748b; }
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e2e8f0; font-size: 0.875rem; }
        th { font-weight: 600; color: #64748b; text-transform: uppercase; font-size: 0.75rem; }
        .bar { height: 0.5rem; background: #e2e8f0; border-radius: 9999px; margin-top: 0.5rem; }
        .bar-fill { height: 100%; border-radius: 9999px; background: #6366f1; }
        .footer { text-align: center; margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; color: #94a3b8; font-size: 0.75rem; }
        @media print { body { background: white; } .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $report['report']['title'] ?? 'AI Visibility Report' }}</h1>
            <p>{{ $report['report']['period'] ?? 'Last 30 days' }} · Generated {{ now()->format('d M Y H:i') }}</p>
            <p>{{ $report['report']['store']['name'] ?? '' }} · {{ $report['report']['store']['domain'] ?? '' }}</p>
        </div>

        <div class="section">
            <h2>Summary</h2>
            <div class="grid">
                <div class="card">
                    <div class="card-label">AI Visibility Score</div>
                    <div class="card-value">{{ $report['summary']['ai_visibility_score'] ?? 0 }}</div>
                </div>
                <div class="card">
                    <div class="card-label">Brand Signals</div>
                    <div class="card-value">{{ $report['summary']['brand_signals_score'] ?? 0 }}</div>
                </div>
                <div class="card">
                    <div class="card-label">Speed Score</div>
                    <div class="card-value">{{ $report['summary']['speed_score'] ?? 0 }}</div>
                </div>
            </div>
        </div>

        <div class="section">
            <h2>AI Traffic</h2>
            <div class="grid">
                <div class="card">
                    <div class="card-label">Total Visits</div>
                    <div class="card-value">{{ number_format($report['summary']['total_ai_traffic'] ?? 0) }}</div>
                </div>
                <div class="card">
                    <div class="card-label">Revenue</div>
                    <div class="card-value">₹{{ number_format($report['summary']['total_ai_revenue'] ?? 0) }}</div>
                </div>
            </div>
        </div>

        @if (!empty($report['trends']['ai_traffic']['by_source']))
        <div class="section">
            <h2>Traffic by Source</h2>
            <table>
                <thead>
                    <tr>
                        <th>Source</th>
                        <th>Visits</th>
                        <th>Revenue</th>
                        <th>Conversions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($report['trends']['ai_traffic']['by_source'] as $source => $stats)
                    <tr>
                        <td style="font-weight: 600; text-transform: capitalize;">{{ $source }}</td>
                        <td>{{ $stats['visits'] ?? 0 }}</td>
                        <td>₹{{ number_format($stats['revenue'] ?? 0) }}</td>
                        <td>{{ $stats['conversions'] ?? 0 }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <div class="section">
            <h2>Content Performance</h2>
            <div class="grid">
                <div class="card">
                    <div class="card-label">Total Posts</div>
                    <div class="card-value">{{ $report['trends']['content_performance']['total_posts'] ?? 0 }}</div>
                </div>
                <div class="card">
                    <div class="card-label">Published</div>
                    <div class="card-value text-emerald">{{ $report['trends']['content_performance']['published'] ?? 0 }}</div>
                </div>
                <div class="card">
                    <div class="card-label">Total Words</div>
                    <div class="card-value">{{ number_format($report['trends']['content_performance']['total_words'] ?? 0) }}</div>
                </div>
            </div>
        </div>

        <div class="footer">
            <p>AI Visibility for Shopify · Generated by AI Visibility App</p>
            <p class="no-print" style="margin-top: 1rem;">
                <button onclick="window.print()" style="padding: 0.5rem 1rem; background: #6366f1; color: white; border: none; border-radius: 0.5rem; cursor: pointer;">Print / Save as PDF</button>
            </p>
        </div>
    </div>
</body>
</html>
