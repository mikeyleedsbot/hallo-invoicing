<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Factuur {{ $invoice->invoice_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10pt;
            color: #1f2937;
            line-height: 1.4;
        }
        
        .container {
            padding: 30px;
        }
        
        /* Header */
        .header {
            margin-bottom: 40px;
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 20px;
        }
        
        .company-info {
            float: left;
            width: 50%;
        }
        
        .company-name {
            font-size: 18pt;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 5px;
        }
        
        .company-details {
            color: #6b7280;
            font-size: 9pt;
            line-height: 1.6;
        }
        
        .invoice-title {
            float: right;
            text-align: right;
            width: 50%;
        }
        
        .invoice-title h1 {
            font-size: 24pt;
            color: #1e40af;
            margin-bottom: 5px;
        }
        
        .invoice-number {
            font-size: 12pt;
            color: #6b7280;
            font-weight: bold;
        }
        
        .clearfix::after {
            content: "";
            display: table;
            clear: both;
        }
        
        /* Info section */
        .info-section {
            margin-bottom: 30px;
        }
        
        .info-block {
            float: left;
            width: 48%;
            margin-right: 4%;
        }
        
        .info-block:last-child {
            margin-right: 0;
        }
        
        .info-label {
            font-size: 8pt;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            font-weight: bold;
        }
        
        .info-content {
            background: #f9fafb;
            padding: 12px;
            border-left: 3px solid #3b82f6;
            margin-bottom: 15px;
        }
        
        .customer-name {
            font-weight: bold;
            font-size: 11pt;
            margin-bottom: 3px;
        }
        
        .date-grid {
            display: table;
            width: 100%;
        }
        
        .date-item {
            display: table-row;
        }
        
        .date-label {
            display: table-cell;
            font-weight: bold;
            padding: 3px 10px 3px 0;
            width: 45%;
        }
        
        .date-value {
            display: table-cell;
            padding: 3px 0;
        }
        
        /* Invoice lines table */
        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .invoice-table thead {
            background: #1e40af;
            color: white;
        }
        
        .invoice-table th {
            padding: 10px;
            text-align: left;
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .invoice-table th:last-child,
        .invoice-table td:last-child {
            text-align: right;
        }
        
        .invoice-table tbody tr {
            border-bottom: 1px solid #e5e7eb;
        }
        
        .invoice-table tbody tr:nth-child(even) {
            background: #f9fafb;
        }
        
        .invoice-table td {
            padding: 10px;
            font-size: 9pt;
        }
        
        .line-description {
            font-weight: bold;
            color: #374151;
        }
        
        /* Totals section */
        .totals-section {
            float: right;
            width: 45%;
        }
        
        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .totals-table tr {
            border-bottom: 1px solid #e5e7eb;
        }
        
        .totals-table td {
            padding: 8px 10px;
        }
        
        .totals-table .label {
            font-weight: bold;
            color: #6b7280;
        }
        
        .totals-table .value {
            text-align: right;
            font-weight: bold;
        }
        
        .totals-table .total-row {
            background: #1e40af;
            color: white;
            font-size: 12pt;
        }
        
        .totals-table .total-row td {
            padding: 12px 10px;
        }
        
        /* Footer */
        .footer {
            margin-top: 60px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            clear: both;
        }
        
        .payment-info {
            background: #f0f9ff;
            border: 1px solid #bfdbfe;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
        
        .payment-info h3 {
            color: #1e40af;
            font-size: 10pt;
            margin-bottom: 8px;
        }
        
        .payment-details {
            font-size: 9pt;
            line-height: 1.8;
        }
        
        .payment-label {
            font-weight: bold;
            display: inline-block;
            width: 120px;
        }
        
        .footer-note {
            text-align: center;
            color: #9ca3af;
            font-size: 8pt;
            margin-top: 20px;
        }
        
        /* Status badge */
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .status-draft { background: #f3f4f6; color: #374151; }
        .status-sent { background: #dbeafe; color: #1e40af; }
        .status-paid { background: #d1fae5; color: #065f46; }
        .status-overdue { background: #fee2e2; color: #991b1b; }
        .status-cancelled { background: #f3f4f6; color: #6b7280; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header clearfix">
            <div class="company-info">
                <div class="company-name">Hallo ICT</div>
                <div class="company-details">
                    Reactorweg 301<br>
                    3542 AD Utrecht<br>
                    Nederland<br><br>
                    KvK: 12345678<br>
                    BTW: NL123456789B01<br>
                    info@hallo.nl<br>
                    +31 (0)30 123 4567
                </div>
            </div>
            
            <div class="invoice-title">
                <h1>FACTUUR</h1>
                <div class="invoice-number">{{ $invoice->invoice_number }}</div>
                <div style="margin-top: 10px;">
                    <span class="status-badge status-{{ strtolower($invoice->status) }}">
                        {{ ucfirst($invoice->status) }}
                    </span>
                </div>
            </div>
        </div>
        
        <!-- Info Section -->
        <div class="info-section clearfix">
            <div class="info-block">
                <div class="info-label">Klant</div>
                <div class="info-content">
                    <div class="customer-name">{{ $invoice->customer->name }}</div>
                    @if($invoice->customer->company_name)
                        <div>{{ $invoice->customer->company_name }}</div>
                    @endif
                    @if($invoice->customer->address)
                        <div style="margin-top: 5px;">{{ $invoice->customer->address }}</div>
                    @endif
                    @if($invoice->customer->postal_code || $invoice->customer->city)
                        <div>{{ $invoice->customer->postal_code }} {{ $invoice->customer->city }}</div>
                    @endif
                    @if($invoice->customer->country)
                        <div>{{ $invoice->customer->country }}</div>
                    @endif
                    @if($invoice->customer->vat_number)
                        <div style="margin-top: 5px;">BTW: {{ $invoice->customer->vat_number }}</div>
                    @endif
                </div>
            </div>
            
            <div class="info-block">
                <div class="info-label">Factuurdetails</div>
                <div class="info-content">
                    <div class="date-grid">
                        <div class="date-item">
                            <span class="date-label">Factuurdatum:</span>
                            <span class="date-value">{{ $invoice->invoice_date->format('d-m-Y') }}</span>
                        </div>
                        <div class="date-item">
                            <span class="date-label">Vervaldatum:</span>
                            <span class="date-value">{{ $invoice->due_date->format('d-m-Y') }}</span>
                        </div>
                        <div class="date-item">
                            <span class="date-label">Betalingstermijn:</span>
                            <span class="date-value">{{ $invoice->payment_terms }} dagen</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Invoice Lines -->
        <table class="invoice-table">
            <thead>
                <tr>
                    <th style="width: 50%;">Omschrijving</th>
                    <th style="width: 12%; text-align: center;">Aantal</th>
                    <th style="width: 13%; text-align: right;">Prijs</th>
                    <th style="width: 10%; text-align: center;">BTW</th>
                    <th style="width: 15%; text-align: right;">Totaal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->lines as $line)
                <tr>
                    <td>
                        <div class="line-description">{{ $line->description }}</div>
                    </td>
                    <td style="text-align: center;">{{ number_format($line->quantity, 0, ',', '.') }}</td>
                    <td style="text-align: right;">€ {{ number_format($line->unit_price, 2, ',', '.') }}</td>
                    <td style="text-align: center;">{{ $line->vat_rate }}%</td>
                    <td style="text-align: right;">€ {{ number_format($line->quantity * $line->unit_price, 2, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        
        <!-- Totals -->
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td class="label">Subtotaal (excl. BTW)</td>
                    <td class="value">€ {{ number_format($invoice->subtotal, 2, ',', '.') }}</td>
                </tr>
                <tr>
                    <td class="label">BTW</td>
                    <td class="value">€ {{ number_format($invoice->vat_amount, 2, ',', '.') }}</td>
                </tr>
                <tr class="total-row">
                    <td>TOTAAL (incl. BTW)</td>
                    <td>€ {{ number_format($invoice->total, 2, ',', '.') }}</td>
                </tr>
            </table>
            @if($invoice->vat_reverse_charged)
            <div style="margin-top: 12px; padding: 10px 12px; border: 1px solid #f59e0b; background: #fffbeb; font-size: 11px; color: #78350f;">
                <strong>BTW verlegd</strong> naar BTW-nummer afnemer{{ $invoice->customer->vat_number ? ': ' . $invoice->customer->vat_number : '' }}.
            </div>
            @endif
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <div class="payment-info">
                <h3>💳 Betalingsinformatie</h3>
                <div class="payment-details">
                    <div><span class="payment-label">Rekeningnummer:</span> NL12 INGB 0001 2345 67</div>
                    <div><span class="payment-label">Ten name van:</span> Hallo ICT B.V.</div>
                    <div><span class="payment-label">Onder vermelding van:</span> {{ $invoice->invoice_number }}</div>
                    <div><span class="payment-label">Betalen voor:</span> {{ $invoice->due_date->format('d-m-Y') }}</div>
                </div>
            </div>
            
            <div class="footer-note">
                Bedankt voor uw vertrouwen in Hallo ICT!<br>
                Voor vragen over deze factuur kunt u contact met ons opnemen via info@hallo.nl
            </div>
        </div>
    </div>
</body>
</html>
