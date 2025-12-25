<?php

namespace Eyecuejohn\TraVfd\Services;

use Barryvdh\DomPDF\Facade\Pdf;

class TraPdfService
{
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Generate a thermal-style PDF receipt based on the Eyecuemedia sample.
     */
    public function makeReceipt($order, array $items)
    {
        $pdf = Pdf::loadView('tra-vfd::receipt-pdf', [
            'order' => $order,
            'items' => $items,
            'config' => $this->config
        ]);

        return $pdf->output();
    }
}
