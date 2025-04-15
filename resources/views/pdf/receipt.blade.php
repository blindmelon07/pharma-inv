<!DOCTYPE html>
<html>
<head>
    <title>Transaction Receipt</title>
    <style>
        body {
            font-family: monospace;
            font-size: 14px;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
        }
        .logo {
            max-width: 80px;
            margin-bottom: 10px;
        }
        .receipt-table,
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .receipt-table th,
        .receipt-table td,
        .summary-table td {
            padding: 4px 0;
        }
        .receipt-table th {
            border-bottom: 1px dashed #000;
        }
        .summary-table td {
            text-align: right;
        }
        .summary-table td:first-child {
            text-align: left;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ $logo }}" class="logo" alt="Logo">
        <h2>Pinoy Care Pharma</h2>
        <p>Transaction Receipt</p>
    </div>

    <p><strong>Date:</strong> {{ now()->format('F j, Y H:i:s') }}</p>

    <table class="receipt-table">
        <thead>
            <tr>
                <th style="text-align:left;">Item</th>
                <th style="text-align:right;">Price</th>
                <th style="text-align:right;">Qty</th>
                <th style="text-align:right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cart as $item)
            <tr>
                <td>{{ $item['name'] }}</td>
                <td style="text-align:right;">{{ number_format($item['price'], 2) }}</td>
                <td style="text-align:right;">{{ $item['quantity'] }}</td>
                <td style="text-align:right;">{{ number_format($item['total'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="summary-table">
        <tr>
            <td>Subtotal:</td>
            <td>PHP{{ number_format($subtotal, 2) }}</td>
        </tr>
        <tr>
            <td>Tax (12%):</td>
            <td>PHP{{ number_format($tax, 2) }}</td>
        </tr>
        <tr>
            <td><strong>Total:</strong></td>
            <td><strong>PHP{{ number_format($total, 2) }}</strong></td>
        </tr>
        <tr>
            <td>Amount Paid:</td>
            <td>PHP{{ number_format($amountPaid, 2) }}</td>
        </tr>
        <tr>
            <td>Change:</td>
            <td>PHP{{ number_format($change, 2) }}</td>
        </tr>
    </table>

    <div class="footer">
        <p>Thank you for shopping with us!</p>
        <p>*** Keep this receipt for your records ***</p>
    </div>
</body>
</html>
