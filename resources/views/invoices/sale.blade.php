<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        @page { size: A4 portrait; margin: 18mm 16mm 20mm; }
        body { color: #1c2b23; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
        h1 { color: #14532d; font-size: 23px; margin: 0 0 6px; }
        h2 { color: #14532d; font-size: 17px; margin: 25px 0 10px; }
        p { line-height: 1.45; margin: 2px 0; }
        .muted { color: #5c6b62; }
        .brand-header { border-bottom: 2px solid #166534; margin: 0 0 22px; }
        .brand-header td { border: 0; padding: 0 0 15px; vertical-align: middle; }
        .brand-details { width: 72%; }
        .brand-logo { text-align: right; width: 28%; }
        .logo { height: 82px; max-width: 150px; }
        .invoice-heading { color: #14532d; font-size: 17px; font-weight: bold; margin: 0 0 10px; }
        .invoice-meta { background: #f0f6f1; }
        .invoice-meta th, .invoice-meta td { border: 0; padding: 8px 10px; }
        .invoice-meta th { color: #52635a; font-size: 9px; text-transform: uppercase; }
        .section-title { color: #14532d; font-size: 11px; font-weight: bold; margin: 20px 0 7px; }
        table { border-collapse: collapse; width: 100%; }
        .customer-details { margin-top: 16px; }
        .customer-details th, .customer-details td { border: 1px solid #dce5de; padding: 8px 10px; text-align: left; }
        .customer-details th { background: #f6f9f6; color: #52635a; width: 18%; }
        .items { margin-top: 8px; }
        .items th, .items td { border: 1px solid #d5ded8; padding: 9px 8px; text-align: left; }
        .items th { background: #eaf5ed; color: #14532d; font-size: 9px; }
        .number { text-align: right !important; white-space: nowrap; }
        .totals { margin: 12px 0 0 auto; width: 48%; }
        .totals td { border: 0; padding: 5px 8px; }
        .grand-total td { border-top: 1px solid #8da597; color: #14532d; font-size: 12px; font-weight: bold; padding-top: 8px; }
        .signatures { margin-top: 170px; }
        .signatures td { border: 0; padding: 0 28px; text-align: center; vertical-align: bottom; width: 50%; }
        .signature-line { border-top: 1px solid #52635a; padding-top: 9px; }
    </style>
</head>
<body>
    <table class="brand-header">
        <tbody>
            <tr>
                <td class="brand-details">
                    <h1>{{ $settings?->farm_name ?? 'Nature Agro' }}</h1>
                    @if ($settings?->owner_name)
                        <p>{{ $settings->owner_name }}</p>
                    @endif
                    @if ($settings?->phone_number)
                        <p>{{ $settings->phone_number }}</p>
                    @endif
                    @if ($settings?->address)
                        <p>{{ $settings->address }}</p>
                    @endif
                    @if ($settings?->description)
                        <p class="muted">{{ $settings->description }}</p>
                    @endif
                </td>
                <td class="brand-logo">
                    @if ($logoDataUri)
                        <img class="logo" src="{{ $logoDataUri }}" alt="Nature Agro logo">
                    @endif
                </td>
            </tr>
        </tbody>
    </table>

    <div class="invoice-heading">SALE INVOICE</div>
    <table class="invoice-meta">
        <tr>
            <th>Invoice</th>
            <td>{{ $sale->invoice_number }}</td>
            <th>Sale date</th>
            <td>{{ $sale->sold_on->format('d M Y') }}</td>
        </tr>
    </table>

    <div class="section-title">Customer</div>
    <table class="customer-details">
        <tr>
            <th>Name</th>
            <td>{{ $sale->customer?->name ?? 'Walk-in customer' }}</td>
            <th>Phone</th>
            <td>{{ $sale->customer?->phone_number ?? '—' }}</td>
        </tr>
        @if ($sale->customer?->address)
            <tr>
                <th>Address</th>
                <td colspan="3">{{ $sale->customer->address }}</td>
            </tr>
        @endif
    </table>

    <div class="section-title">Product details</div>
    <table class="items">
        <thead>
            <tr>
                <th>Product</th>
                <th>Batch / source</th>
                <th class="number">Quantity</th>
                <th class="number">Unit price</th>
                <th class="number">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $productName }}</td>
                <td>{{ $stockSource ?: '—' }}</td>
                <td class="number">{{ number_format($sale->quantity) }}</td>
                <td class="number">{{ $currency }} {{ number_format((float) $sale->unit_price, 2) }}</td>
                <td class="number">{{ $currency }} {{ number_format($sale->quantity * (float) $sale->unit_price, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="totals">
        <tbody>
            <tr>
                <td>Subtotal</td>
                <td class="number">{{ $currency }} {{ number_format((float) $sale->total_amount + (float) $sale->discount_amount, 2) }}</td>
            </tr>
            <tr>
                <td>Discount</td>
                <td class="number">{{ $currency }} {{ number_format((float) $sale->discount_amount, 2) }}</td>
            </tr>
            <tr class="grand-total">
                <td>Total</td>
                <td class="number">{{ $currency }} {{ number_format((float) $sale->total_amount, 2) }}</td>
            </tr>
            <tr>
                <td>Paid</td>
                <td class="number">{{ $currency }} {{ number_format((float) $sale->paid_amount, 2) }}</td>
            </tr>
            <tr>
                <td>Due</td>
                <td class="number">{{ $currency }} {{ number_format((float) $sale->due_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="signatures">
        <tr>
            <td><div class="signature-line">Seller signature<br>{{ $settings?->owner_name ?? 'Authorized seller' }}</div></td>
            <td><div class="signature-line">Receiver signature<br>{{ $sale->customer?->name ?? 'Customer' }}</div></td>
        </tr>
    </table>
</body>
</html>
