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
    <title>Fiscal Receipt</title>
    <style>
        @page { margin: 0; }
        body {
            font-family: 'Courier', monospace; /* Best for thermal alignment */
            font-size: 12px;
            width: 80mm;
            padding: 5mm;
            margin: 0;
            line-height: 1.4;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }
        .divider { border-bottom: 1px dashed #000; margin: 5px 0; }
        
        table { width: 100%; border-collapse: collapse; }
        .items-table th { text-align: left; border-bottom: 1px solid #000; }
        
        .qr-code { margin: 10px auto; width: 40mm; }
        .qr-code img { width: 100%; height: auto; }
        
        .fiscal-data { font-size: 10px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="text-center">
        <h2 style="margin-bottom: 5px;">{{ config('app.name') }}</h2>
        <p>TIN: {{ config('tra.tin') }}<br>
        Serial: {{ config('tra.cert_serial') }}</p>
    </div>

    <div class="divider"></div>

    <table>
        <tr><td>Date:</td><td class="text-right">{{ date('d-m-Y H:i') }}</td></tr>
        <tr><td>Receipt #:</td><td class="text-right">{{ $order->tra_receipt_num }}</td></tr>
    </table>

    <div class="divider"></div>

    <table class="items-table">
        <thead>
            <tr>
                <th>Item</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>{{ $item->desc }}</td>
                <td class="text-right">{{ $item->qty }}</td>
                <td class="text-right">{{ number_format($item->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="divider"></div>

    <table>
        <tr class="bold">
            <td>TOTAL</td>
            <td class="text-right">TZS {{ number_format($order->amount, 2) }}</td>
        </tr>
    </table>

    <div class="text-center">
        <div class="qr-code">
            <img src="data:image/png;base64, {!! base64_encode(TraVfd::generateQrCode($order->tra_verify_url)) !!} ">
        </div>
        <p class="fiscal-data">
            FISCAL RECEIPT<br>
            Verify at: {{ config('tra.verify_url') }}
        </p>
    </div>

    <div class="text-center bold">
        *** THANK YOU ***
    </div>
</body>
</html>
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
