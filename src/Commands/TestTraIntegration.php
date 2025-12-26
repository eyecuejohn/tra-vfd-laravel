<?php

/**
 * Package: eyecuejohn/tra-vfd-laravel
 * Author: John M Kagaruki (john@eyecuemedia.co.tz)
 * License: MIT
 * Copyright: (c) 2025 John M Kagaruki (Eyecuejohn)
 */

namespace Eyecuejohn\TraVfd\Commands;

use Illuminate\Console\Command;
use Eyecuejohn\TraVfd\Facades\TraVfd;

class TestTraIntegration extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tra-vfd:test';

    /**
     * The console command description.
     */
    protected $description = 'Run a smoke test to verify TRA VFD integration and signing';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Starting TRA VFD Smoke Test...');

        // 1. Prepare Mock Data
        $items = [
            [
                'desc' => 'Smoke Test Service',
                'qty' => 1,
                'unit_price' => 1000.00,
                'amount' => 1000.00,
                'tax_code' => 'A'
            ]
        ];

        $orderData = [
            'cust_name' => 'Test Customer',
            'cust_id'   => 'NIL',
            'cust_id_type' => '1',
            'z_num'     => date('Ymd'),
            'total_excl' => 847.46,
            'total_tax' => 152.54,
            'total_amount' => 1000.00,
        ];

        try {
            $this->comment('Step 1: Attempting to submit to TRA (this will handle signing & sequencing)...');
            
            // This calls the manager method
            $response = TraVfd::submitReceiptWithFallback($items, $orderData);

            if ($response['status'] === 'success') {
                $this->info('✅ SUCCESS: Receipt submitted to TRA successfully!');
                $this->line('Receipt Number: ' . ($response['data']['rct_num'] ?? 'N/A'));
            } elseif ($response['status'] === 'queued') {
                $this->warn('⚠️ QUEUED: API unreachable, but fallback queue worked perfectly.');
                $this->line('Reason: ' . $response['error']);
            } else {
                $this->error('❌ FAILED: ' . ($response['message'] ?? 'Unknown error'));
            }

        } catch (\Exception $e) {
            $this->error('❌ EXCEPTION: ' . $e->getMessage());
            $this->comment('Check your .env settings and that your .pfx file exists in storage/app/tra/cert.pfx');
        }

        return 0;
    }
}
