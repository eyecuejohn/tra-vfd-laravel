<?php

namespace Eyecuejohn\TraVfd;

use Barryvdh\DomPDF\Facade\Pdf;
use Eyecuejohn\TraVfd\Mail\TraReceiptMailer;
use Illuminate\Support\Facades\Mail;

class TraVfdManager
{
    protected $app;
    protected $config;

    public function __construct($app)
    {
        $this->app = $app;
        $this->config = $app['config']['tra-vfd'];
    }

    /**
     * Prepare the PDF instance with official TRA WebVFD layout requirements.
     */
    public function makeReceiptPdf(array $items, array $order)
    {
        // 1. Calculations
        $totalAmount = collect($items)->sum('amount');
        $totalTax = collect($items)->where('tax_code', 'A')->sum(fn($i) => $i['amount'] - ($i['amount'] / 1.18));
        
        // 2. Local QR URL Generation
        $verifyUrl = $order['verify_url'] ?? "https://virtual.tra.go.tz/efdmsRctVerify/Home/Index?" . http_build_query([
            'tin' => $this->config['tin'],
            'receipt_num' => $order['rct_num'] ?? '0',
            'date' => $order['date'] ?? date('Y-m-d'),
            'time' => $order['time'] ?? date('H:i:s')
        ]);

        // 3. Logo pathing
        $logoPath = __DIR__ . '/../resources/assets/tra-logo.png';
        $logoBase64 = file_exists($logoPath) ? base64_encode(file_get_contents($logoPath)) : '';

        // 4. Enrich data to prevent Undefined Key errors (Date, Time, Serial, UIN)
        $enrichedOrder = array_merge($order, [
            'customer_name'     => $order['customer_name'] ?? ($order['custname'] ?? 'N/A'),
            'customer_id'       => $order['customer_id'] ?? ($order['custid'] ?? 'N/A'),
            'date'              => $order['date'] ?? date('Y-m-d'), // Fixes line 59
            'time'              => $order['time'] ?? date('H:i:s'), // Fixes line 60
            'serial_no'         => $order['serial_no'] ?? ($this->config['cert_serial'] ?? '10TZ125104'),
            'uin'               => $order['uin'] ?? ('09VFDWEBAPI-' . time() . $this->config['tin']),
            'total_amount'      => $totalAmount,
            'total_tax'         => $totalTax,
            'total_excl'        => $totalAmount - $totalTax,
            'verify_url'        => $verifyUrl,
            'verification_code' => $order['verification_code'] ?? strtoupper(substr(md5(($order['rct_num'] ?? time())), 0, 8)),
        ]);

        $data = [
            'items'  => $items,
            'logo'   => $logoBase64,
            'order'  => $enrichedOrder,
            'config' => $this->config
        ];

        return Pdf::loadView('tra-vfd::receipt-pdf', $data)
                  ->setPaper([0, 0, 226.77, 841.89], 'portrait');
    }

    // ... (rest of your methods: getReceiptForSharing, emailReceipt, submitReceiptWithFallback)
}