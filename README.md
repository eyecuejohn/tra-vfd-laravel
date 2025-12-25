Laravel TRA VFD Integration

A specialized Laravel package for seamless integration with the Tanzania Revenue Authority (TRA) VFD (Virtual Fiscal Device) API. Designed to handle the complexities of digital signing, sequential numbering, and fiscal reporting with ease.
✨ Key Features

    • Automated Digital Signing: Industrial-grade SHA1 signing using xmlseclibs and your TRA .pfx certificate.

    • Atomic Counter Management: Uses database-level row locking to ensure GC and RCTNUM sequences are never duplicated.

    • Resilient Design: JIT (Just-In-Time) token retrieval and offline fallback queueing for high reliability.

    • Ready-to-use QR Support: Built-in QR code generation for instant TRA verification links.

    • VFD Registration: Simplified one-time registration process to obtain your Routing Key.
    Thermal PDF Support: Native 80mm PDF generation compliant with TRA WebVFD layout requirements.

    • Receipt Sharing: Built-in methods for streaming, downloading, or emailing fiscal receipts to customers.

📋 Installation
1. Requirements

    • PHP: ^8.2

    • Laravel: 9.0 | 10.0 | 11.0 | 12.0

    • Extensions: openssl, dom, curl, simplexml

2. Install via Composer

```
composer require eyecuejohn/tra-vfd-laravel

```

⚙️ Configuration
1. Environment Setup

Add the following to your .env file:

#Code snippet

```
TRA_TIN=123456789
TRA_CERT_SERIAL=VFD-SERIAL-001
TRA_CERT_PASSWORD=your_certificate_password
TRA_API_URL=https://vfd.tra.go.tz
TRA_VERIFY_URL=https://verify.tra.go.tz
TRA_VAT_REGISTERED=true
TRA_ROUTING_KEY=your_registration_key
```

2. Certificate Security

Store your .pfx certificate file at: storage/app/tra/cert.pfx

    [!WARNING] Security Note: Ensure *.pfx is added to your .gitignore. Never commit your certificate or production passwords to version control.

🛠️ Usage
One-Time Registration

3. Publish & Migrate

```
# Publish configuration
php artisan vendor:publish --tag="tra-vfd-config"

# Run migrations
php artisan migrate
```

Before issuing receipts, you must register your VFD.

```
use Eyecuejohn\TraVfd\Facades\TraVfd;

$response = TraVfd::registerVfd('YOUR_CERT_KEY_CONTENT');
// This returns the ROUTING_KEY required for subsequent requests.
```

Issuing a Fiscal Receipt

The submitReceiptWithFallback method handles the token request, XML signing, sequence management, and API submission in one go.

```
public function issue(Order $order) 
{
    $data = [
        'custname' => $order->customer_name,
        'custid'   => $order->tin ?? 'NIL',
        'items'    => [
            [
                'desc'     => 'Professional Consulting', 
                'qty'      => 1, 
                'amount'   => 150000.00, 
                'tax_code' => 'A' // A=18%, B=0%, C=Exempt, D=Zero Rated, E=Special
            ]
        ]
    ];

    $response = TraVfd::submitReceiptWithFallback($data);

    if ($response['status'] === 'success') {
        $order->update([
            'tra_receipt_num' => $response['receipt_number'],
            'tra_verify_url'  => $response['verify_url']
        ]);
    }
}
```

Daily Z-Report

TRA requires a Z-Report to be submitted at the close of every business day.

```
TraVfd::submitZReport(
    date: '2025-12-25',
    dailyTotal: 500000.00,
    grossTotal: 10000000.00 // Cumulative total
);
```

Displaying the QR Code (Blade)


📡 Webhooks & Background Jobs (Planned)

The package is designed to be extensible. If the TRA API is down, the "Fallback" mechanism logs the request. You can set up a scheduled task to retry failed submissions.

```
// In app/Console/Kernel.php
$schedule->command('tra:retry-failed')->hourly();
```

Issuing a Fisacl Receipt

```
use Eyecuejohn\TraVfd\Facades\TraVfd;

$response = TraVfd::submitReceiptWithFallback([
    'custname' => 'John Doe',
    'custid'   => 'NIL',
    'items'    => [
        ['desc' => 'Service', 'qty' => 1, 'amount' => 1000.00, 'tax_code' => 'A']
    ]
]);
```

Generating Thermal PDF (New)

With laravel-dompdf, you can use it alongside the TRA data to generate printable receipts:

```
use Barryvdh\DomPDF\Facade\Pdf;

public function downloadReceipt($order) {
    $pdf = Pdf::loadView('receipts.thermal', ['order' => $order]);
    return $pdf->download('fiscal-receipt.pdf');
}
```

Thermal Receipt Blade Template (receipts/thermal.blade.php)

```
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
```

Some .env values

```

# --- TRA VFD SETTINGS ---
TRA_COMPANY_NAME="Your Company Name Ltd"
TRA_COMPANY_ADDRESS="P.O. BOX 000, Dar es Salaam, Tanzania"
TRA_COMPANY_MOBILE="255700000000"
TRA_TAX_OFFICE="Kinondoni"

# Use your dummy TIN for testing
TRA_VRN="NOT REGISTERED"


# API URLs (Using TRA's actual URLs even for tests)
# TRA_API_URL=https://vfd.tra.go.tz
# TRA_VERIFY_URL=https://verify.tra.go.tz
# TRA_ROUTING_KEY=dummy_registration_key_123

# Tax Settings
TRA_VAT_REGISTERED=false

# Taxpayer Info
TRA_TIN=999999999
TRA_CERT_SERIAL=VFD-TEST-001
TRA_CERT_PASSWORD=password123
TRA_CERT_PATH=tra/cert.pfx

# Test Server Endpoints (from TRA Docs)
TRA_API_URL=https://virtual.tra.go.tz/efdmsRctApi
TRA_VERIFY_URL=https://virtual.tra.go.tz/efdmsRctVerify/Home/Index
TRA_ROUTING_KEY=dummy_key_123

# Use these if you want to be specific in your config/tra-vfd.php
TRA_REGISTRATION_ENDPOINT=https://virtual.tra.go.tz/efdmsRctApi/api/vfdRegReq
TRA_TOKEN_ENDPOINT=https://virtual.tra.go.tz/efdmsRctApi/vfdtoken
TRA_RECEIPT_ENDPOINT=https://virtual.tra.go.tz/efdmsRctApi/api/efdmsRctInfo
TRA_ZREPORT_ENDPOINT=https://virtual.tra.go.tz/efdmsRctApi/api/efdmszreport
```

🤝 Contributing

Contributions are what make the open-source community an amazing place to learn, inspire, and create.

    Fork the Project

    Create your Feature Branch (git checkout -b feature/AmazingFeature)

    Commit your Changes (git commit -m 'Add some AmazingFeature')

    Push to the Branch (git push origin feature/AmazingFeature)

    Open a Pull Request




📄 License

MIT License

Copyright © 2025 John M Kagaruki (Eyecuejohn) Dar es Salaam, Tanzania 🇹🇿

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
