<?php

/**
 * Package: eyecuejohn/tra-vfd-laravel
 * Author: John M Kagaruki (john@eyecuemedia.co.tz)
 * License: MIT
 * Copyright: (c) 2025 John M Kagaruki (Eyecuejohn)
 */

namespace Eyecuejohn\TraVfd\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TraReceiptMailer extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    protected $pdfContent;
    protected $filename;

    /**
     * Create a new message instance.
     */
    public function __construct($order, $pdfContent, $filename = 'receipt.pdf')
    {
        $this->order = $order;
        $this->pdfContent = $pdfContent;
        $this->filename = $filename;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        // Use 'rct_num' to match the manager's enriched data
        $receiptId = $this->order['rct_num'] ?? $this->order['receipt_number'] ?? 'N/A';

        return $this->subject('Fiscal Receipt - No: ' . $receiptId)
                    ->view('tra-vfd::emails.receipt-notification')
                    ->attachData($this->pdfContent, $this->filename, [
                        'mime' => 'application/pdf',
                    ]);
    }
}