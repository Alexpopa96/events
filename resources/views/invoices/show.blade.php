@php
    $statusLabels = ['pending' => 'În așteptare', 'paid' => 'Plătită', 'failed' => 'Eșuată', 'refunded' => 'Rambursată'];
@endphp
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="utf-8">
    <title>Factura {{ $invoice->number }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #211C27;
            background: #F6F3EB;
            margin: 0;
            padding: 48px 24px;
        }
        .sheet {
            max-width: 720px;
            margin: 0 auto;
            background: #fff;
            border-radius: 20px;
            padding: 48px;
            box-shadow: 0 20px 50px -20px rgba(33, 28, 39, 0.15);
        }
        .top { display: flex; justify-content: space-between; align-items: flex-start; gap: 24px; }
        .brand { font-family: Georgia, 'Iowan Old Style', serif; font-size: 24px; font-weight: 600; color: #7C2E3B; }
        .pill {
            display: inline-flex; align-items: center; padding: 6px 14px; border-radius: 999px;
            font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
        }
        .pill.paid { background: #E7F3EC; color: #1E7A46; }
        .pill.pending { background: #FBF1DB; color: #8A6420; }
        .pill.failed { background: #FBEAE8; color: #B3413A; }
        .pill.refunded { background: #F1F0EC; color: #6B6570; }
        h1 { font-family: Georgia, serif; font-size: 20px; font-weight: 500; margin: 32px 0 4px; }
        .muted { color: #6B6570; font-size: 13px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin: 32px 0; }
        .grid h3 { font-size: 11px; text-transform: uppercase; letter-spacing: .06em; color: #6B6570; margin: 0 0 8px; }
        .grid p { margin: 2px 0; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { text-align: left; padding: 12px 0; border-bottom: 1px solid #E7E3D8; font-size: 14px; }
        th { font-size: 11px; text-transform: uppercase; letter-spacing: .05em; color: #6B6570; }
        .amount-row td { border-bottom: none; padding-top: 20px; font-size: 18px; font-weight: 600; }
        .note { margin-top: 40px; padding: 16px 20px; background: #F6F3EB; border-radius: 14px; font-size: 12.5px; color: #6B6570; line-height: 1.6; }
        .print-btn {
            display: inline-block; margin-bottom: 24px; padding: 10px 20px; border-radius: 999px;
            background: #7C2E3B; color: #fff; font-size: 13.5px; font-weight: 600; text-decoration: none; border: none; cursor: pointer;
        }
        @media print { .print-btn { display: none; } body { background: #fff; padding: 0; } .sheet { box-shadow: none; border-radius: 0; } }
    </style>
</head>
<body>
    <div class="sheet">
        <button class="print-btn" onclick="window.print()">Descarcă / printează ca PDF</button>

        <div class="top">
            <div>
                <div class="brand">Invita</div>
                <p class="muted">Platformă de furnizori pentru evenimente</p>
            </div>
            <span class="pill {{ $invoice->status }}">{{ $statusLabels[$invoice->status] ?? $invoice->status }}</span>
        </div>

        <h1>Factură {{ $invoice->number }}</h1>
        <p class="muted">Emisă pe {{ optional($invoice->issued_at)->format('d.m.Y') ?? '—' }}</p>

        <div class="grid">
            <div>
                <h3>Facturat către</h3>
                <p><strong>{{ $invoice->providerProfile->company_name }}</strong></p>
                @if($invoice->providerProfile->cui)
                    <p>CUI {{ $invoice->providerProfile->cui }}</p>
                @endif
                @if($invoice->providerProfile->address)
                    <p>{{ $invoice->providerProfile->address }}</p>
                @endif
                @if($invoice->providerProfile->email)
                    <p>{{ $invoice->providerProfile->email }}</p>
                @endif
            </div>
            <div>
                <h3>Detalii plată</h3>
                <p>Status: {{ $statusLabels[$invoice->status] ?? $invoice->status }}</p>
                @if($invoice->paid_at)
                    <p>Plătită pe {{ $invoice->paid_at->format('d.m.Y') }}</p>
                @endif
                <p>Monedă: {{ $invoice->currency }}</p>
            </div>
        </div>

        <table>
            <thead>
                <tr><th>Descriere</th><th>Sumă</th></tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        Abonament{{ $invoice->subscription?->plan?->name ? ' — plan "'.$invoice->subscription->plan->name.'"' : '' }}
                    </td>
                    <td>{{ number_format((float) $invoice->amount, 2, ',', '.') }} {{ $invoice->currency }}</td>
                </tr>
                <tr class="amount-row">
                    <td>Total</td>
                    <td>{{ number_format((float) $invoice->amount, 2, ',', '.') }} {{ $invoice->currency }}</td>
                </tr>
            </tbody>
        </table>

        <p class="note">
            Acest document este generat direct din contul tău de furnizor și servește ca dovadă a plății către Invita.
            Pentru facturi fiscale complete, contactează echipa de suport.
        </p>
    </div>
</body>
</html>
