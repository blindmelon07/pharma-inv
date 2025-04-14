<!DOCTYPE html>
<html>
<head>
    <title>Transaction Receipt</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table, th, td { border: 1px solid #000; padding: 8px; }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .logo {
            max-width: 100px;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="header">
        <img src="{{ $logo }}" alt="Logo" style="width: 100px; display: block; margin: 0 auto;">
        <h1>Pinoy Care Pharma</h1>
        <h2>Transaction Receipt</h2>
    </div>

    <p><strong>Date:</strong> {{ now()->format('F j, Y H:i:s') }}</p>
    <p><strong>Amount Paid:</strong> PHP{{ number_format($amountPaid, 2) }}</p>
    <p><strong>Change:</strong> PHP{{ number_format($change, 2) }}</p>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Qty</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cart as $item)
            <tr>
                <td>{{ $item['name'] }}</td>
                <td>PHP{{ number_format($item['price'], 2) }}</td>
                <td>{{ $item['quantity'] }}</td>
                <td>PHP{{ number_format($item['total'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
