<?php

/**
 * Package: eyecuejohn/tra-vfd-laravel
 * Author: John M Kagaruki (john@eyecuemedia.co.tz)
 * License: MIT
 * Copyright: (c) 2025 John M Kagaruki (Eyecuejohn)
 */

namespace Eyecuejohn\TraVfd\Services;

use SimpleXMLElement;

class XmlGeneratorService
{
    /**
     * The package configuration.
     */
    protected array $config;

    public function __construct()
    {
        $this->config = config('tra-vfd');
    }

    /**
     * Generate the raw XML for a Fiscal Receipt.
     *
     * @param array $items List of items (desc, qty, unit_price, amount, tax_code)
     * @param array $order Order meta data (customer_name, customer_id, rct_num, gc, z_num, etc.)
     * @return string
     */
    public function generateReceiptXml(array $items, array $order): string
    {
        // TRA requires a very specific XML root
        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><RECEIPT></RECEIPT>');
        
        // Header Information (Matches Swiss Embassy PDF layout)
        $xml->addChild('DATE', $order['date'] ?? date('Y-m-d')); // [cite: 18]
        $xml->addChild('TIME', $order['time'] ?? date('H:i:s')); // [cite: 19]
        $xml->addChild('TIN', $this->config['tin']); // [cite: 5, 27]
        $xml->addChild('REGID', $this->config['cert_serial']); // [cite: 7]
        $xml->addChild('EFDSERIAL', $this->config['cert_serial']);
        $xml->addChild('REGISTRATIONNUMBER', $this->config['vrn'] ?? 'NOT REGISTERED'); // [cite: 6, 28]
        
        // Counters
        $xml->addChild('ZNUMBER', $order['z_num']); // [cite: 17, 34]
        $xml->addChild('RECPTNUM', $order['rct_num']); // [cite: 16, 33]
        $xml->addChild('GCNUM', $order['gc']);
        
        // Customer Details (Aligned with your Swiss Embassy sample)
        $xml->addChild('CUSTNAME', htmlspecialchars($order['customer_name'] ?? 'NIL')); // [cite: 10]
        $xml->addChild('CUSTIDTYPE', $this->mapCustomerIdType($order['customer_id_type'] ?? 'TIN')); // [cite: 11]
        $xml->addChild('CUSTID', $order['customer_id'] ?? 'NIL'); // [cite: 12]
        
        // Items Loop
        $itemsNode = $xml->addChild('ITEMS');
        foreach ($items as $item) {
            $itemNode = $itemsNode->addChild('ITEM');
            $itemNode->addChild('ID', htmlspecialchars($item['desc'])); // [cite: 20, 30]
            $itemNode->addChild('QTY', $item['qty']); // [cite: 20, 31]
            $itemNode->addChild('AMT', number_format($item['amount'], 2, '.', '')); // [cite: 21, 31]
            $itemNode->addChild('TAXCODE', $item['tax_code'] ?? 'A');
        }

        // Totals (Tax Summary Table alignment)
        $xml->addChild('TOTALS');
        $xml->TOTALS->addChild('TOTALTAXEXCL', number_format($order['total_excl'], 2, '.', '')); // [cite: 22]
        $xml->TOTALS->addChild('TOTALTAXINCL', number_format($order['total_amount'], 2, '.', '')); // [cite: 22, 32]
        $xml->TOTALS->addChild('DISCOUNT', '0.00');

        return $xml->asXML();
    }

    /**
     * Map string ID types to TRA numeric codes.
     */
    protected function mapCustomerIdType(string $type): int
    {
        $map = [
            'TIN'      => 1,
            'PASSPORT' => 2,
            'DRIVING'  => 3,
            'VOTER'    => 4,
            'NID'      => 5, // National ID
            'NIL'      => 6
        ];

        return $map[strtoupper($type)] ?? 6;
    }
}