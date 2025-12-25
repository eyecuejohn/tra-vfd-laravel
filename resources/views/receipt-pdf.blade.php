<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        /* Thermal roll rendering with flexible height */
        @page { 
            margin: 5; 
            size: 80mm auto; 
        }
        
        body {
            font-family: 'Arial', sans-serif;
            width: 70mm;
            margin: 0;
            /* 10px horizontal padding to force text away from edges and stop bleed */
            padding: 15px 15px; 
            font-size: 7pt;    
            line-height: 1.8;
            color: #000;
            height: auto;
            overflow: hidden;
            box-sizing: border-box;
        }

        .text-center { text-align: center; }
        .bold { font-weight: bold; }
        
        /* Solid straight line divider */
        .divider { 
            border-top: 1px solid #000; 
            margin: 10px 0; 
            width: 100%;
        }

        /* Standard flow for label and data sitting next to each other */
        .info-row {
            text-align: left;
            margin-bottom: 1px;
            word-wrap: break-word;
        }

        table { 
            width: 100%; 
            border-collapse: collapse; 
            table-layout: fixed; 
            margin: 0; 
        }

        td { 
            vertical-align: top; 
            word-wrap: break-word; 
            padding: 1.5px 0;
        }

        .left-align { text-align: left; }
        .right-align { text-align: right; }
        
        .logo-container { text-align: center; margin: 5px 0; }
        .logo { width: 35mm; height: auto; display: inline-block; }
        
        .qr-code { margin: 10px auto 5px auto; width: 35mm; text-align: center; }
        .qr-code img { width: 35mm; height: 35mm; display: inline-block; }
    </style>
</head>
<body>
    <div class="text-center">
        <div class="bold">*** START OF LEGAL RECEIPT ***</div>
        
        <div class="logo-container">
            @if($logo)
                <img src="data:image/png;base64,{{ $logo }}" class="logo">
            @endif
        </div>

        <div>TRA</div>
        <div class="bold">{{ $config['company_name'] }}</div>
        <div>{{ $config['company_address'] ?? '' }}</div>
        <div><span class="bold">Mobile:</span> {{ $config['company_mobile'] ?? '' }}</div>
        <div><span class="bold">TIN:</span> {{ $config['tin'] }}</div>
        <div><span class="bold">VRN:</span> {{ $config['vrn'] ?? 'NOT REGISTERED' }}</div>
        <div><span class="bold">SERIAL NO:</span> {{ $order['serial_no'] }}</div>
        <div><span class="bold">UIN:</span> {{ $order['uin'] }}</div>
        <div><span class="bold">TAX OFFICE:</span> {{ $config['tax_office'] ?? '' }}</div>
    </div>

    <div class="divider"></div>

    <div class="info-row">CUSTOMER NAME: {{ $order['customer_name'] }}</div>
    <div class="info-row">CUSTOMER ID TYPE: {{ $order['customer_id_type'] ?? 'Taxpayer Identification Number' }}</div>
    <div class="info-row">CUSTOMER ID: {{ $order['customer_id'] }}</div>
    <div class="info-row">CUSTOMER VRN: {{ $order['customer_vrn'] ?? '' }}</div>
    <div class="info-row">CUSTOMER MOBILE: {{ $order['customer_mobile'] ?? '' }}</div>
    <div class="info-row">CUSTOMER ADDRESS: {{ $order['customer_address'] ?? '' }}</div>

    <div class="divider"></div>

    <div class="info-row">RECEIPT NUMBER: {{ $order['rct_num'] }}</div>
    <div class="info-row">Z NUMBER: {{ $order['z_num'] }}</div>
    <div class="info-row">RECEIPT DATE: {{ $order['date'] }}</div>
    <div class="info-row">RECEIPT TIME: {{ $order['time'] }}</div>

    <div class="divider"></div>

    <table>
        @foreach($items as $item)
            <tr><td colspan="2" class="text-center">{{ $item['desc'] }}</td></tr>
            <tr>
                <td class="left-align" style="width: 55%;">&nbsp;&nbsp;{{ $item['qty'] }} x {{ number_format($item['unit_price'], 2) }}</td>
                <td class="right-align" style="width: 45%;">{{ number_format($item['amount'], 2) }} {{ $item['tax_code'] }}</td>
            </tr>
        @endforeach
    </table>

    <div class="divider"></div>

    <table>
        <tr>
            <td class="left-align bold" style="width: 60%;">TOTAL EXCL OF TAX:</td>
            <td class="right-align bold" style="width: 40%;">{{ number_format($order['total_excl'], 2) }}</td>
        </tr>
        <tr>
            <td class="left-align bold">TOTAL TAX:</td>
            <td class="right-align bold">{{ number_format($order['total_tax'], 2) }}</td>
        </tr>
        <tr>
            <td class="left-align bold">TOTAL INCL OF TAX:</td>
            <td class="right-align bold">{{ number_format($order['total_amount'], 2) }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <div class="text-center">
        <div class="bold">RECEIPT VERIFICATION CODE</div>
        <div class="bold" style="font-size: 10pt; letter-spacing: 1px;">{{ $order['verification_code'] }}</div>
        
        <div class="qr-code">
             <img src="data:image/png;base64, {!! base64_encode(QrCode::format('png')->size(150)->margin(0)->generate($order['verify_url'])) !!} ">
        </div>
        
        <div style="margin: 5px 0;">***</div>
        <div class="bold">END OF LEGAL RECEIPT ***</div>
    </div>
</body>
</html>