<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 0px; }
        body { 
            font-family: 'Courier', monospace; 
            font-size: 11px; 
            width: 80mm; 
            margin: 0 auto; 
            padding: 10px;
        }
        .center { text-align: center; }
        .bold { font-weight: bold; }
        .line { border-bottom: 1px dashed #000; margin: 5px 0; }
        .right { text-align: right; }
        table { width: 100%; border-collapse: collapse; }
        .qr-code { margin: 10px 0; }
        .logo { width: 60px; height: auto; margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="center">
        <img src="data:image/svg+xml;base64,{{ base64_encode(file_get_contents(public_path('vendor/tra-vfd/tra-logo.svg'))) }}" class="logo">
        <br>
        <span class="bold">*** START OF LEGAL RECEIPT ***</span><br>
        TRA<br>
        <span class="bold">{{ $config['company_name'] }}</span><br>
        {{ $config['company_address'] ?? '' }}<br>
        Mobile: {{ $config['company_mobile'] ?? '' }}<br>
        TIN: {{ $config['tin'] }}<br>
        VRN: {{ $config['vrn'] ?? 'NOT REGISTERED' }}<br>
        SERIAL NO: {{ $order['serial_no'] ?? '10TZ125104' }}<br>
        UIN: {{ $order['uin'] ?? '' }}<br>
        TAX OFFICE: {{ $config['tax_office'] ?? '' }}<br>
    </div>

    <div class="line"></div>

    <div>
        CUSTOMER NAME: {{ $order['customer_name'] ?? 'N/A' }}<br>
        CUSTOMER ID TYPE: {{ $order['customer_id_type'] ?? 'TIN' }}<br>
        CUSTOMER ID: {{ $order['customer_id'] ?? 'N/A' }}<br>
        RECEIPT NUMBER: {{ $order['rct_num'] }}<br>
        Z NUMBER: {{ $order['z_num'] }}<br>
        RECEIPT DATE: {{ $order['date'] ?? date('Y-m-d') }}<br>
        RECEIPT TIME: {{ $order['time'] ?? date('H:i:s') }}<br>
    </div>

    <div class="line"></div>

    <table>
        @foreach($items as $item)
        <tr>
            <td colspan="2">{{ $item['desc'] }}</td>
        </tr>
        <tr>
            <td>&nbsp;&nbsp;{{ $item['qty'] }} x {{ number_format($item['unit_price'], 2) }}</td>
            <td class="right">{{ number_format($item['amount'], 2) }} {{ $item['tax_code'] }}</td>
        </tr>
        @endforeach
    </table>

    <div class="line"></div>

    <table>
        <tr>
            <td>TOTAL EXCL OF TAX:</td>
            <td class="right">{{ number_format($order['total_excl'] ?? 0, 2) }}</td>
        </tr>
        <tr>
            <td>TOTAL TAX:</td>
            <td class="right">{{ number_format($order['total_tax'] ?? 0, 2) }}</td>
        </tr>
        <tr class="bold">
            <td>TOTAL INCL OF TAX:</td>
            <td class="right">{{ number_format($order['total_amount'], 2) }}</td>
        </tr>
    </table>

    <div class="line"></div>

    <div class="center qr-code">
        <img src="data:image/png;base64, {!! base64_encode(QrCode::format('png')->size(120)->margin(0)->generate($order['verify_url'])) !!} ">
        <br>
        RECEIPT VERIFICATION CODE<br>
        <span class="bold">{{ $order['verification_code'] ?? '94A31E40' }}</span><br>
        ***<br>
        <span class="bold">END OF LEGAL RECEIPT ***</span>
    </div>
</body>
</html>