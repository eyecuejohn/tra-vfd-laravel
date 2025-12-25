<?php

/**
 * Package: eyecuejohn/tra-vfd-laravel
 * Author: John M Kagaruki (john@eyecuemedia.co.tz)
 * License: MIT
 * Copyright: (c) 2025 John M Kagaruki (Eyecuejohn)
 */

namespace Eyecuejohn\TraVfd\Commands;

use Illuminate\Console\Command;
use Eyecuejohn\TraVfd\Models\TraVfdQueue;
use Eyecuejohn\TraVfd\Services\ApiService;

class ProcessTraQueue extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tra-vfd:process-queue';

    /**
     * The console command description.
     */
    protected $description = 'Retry sending pending TRA VFD receipts from the offline fallback queue';

    /**
     * Execute the console command.
     */
    public function handle(ApiService $apiService)
    {
        $pendingReceipts = TraVfdQueue::where('status', 'pending')
            ->where('attempts', '<', 5)
            ->get();

        if ($pendingReceipts->isEmpty()) {
            $this->info('No pending TRA receipts to process.');
            return 0;
        }

        $this->info("Processing {$pendingReceipts->count()} pending receipts...");

        foreach ($pendingReceipts as $receipt) {
            $this->comment("Attempting to send Receipt #{$receipt->rct_num}...");

            try {
                // Since the payload is already signed, we send the raw XML directly
                $response = $apiService->submitReceipt($receipt->xml_payload, $receipt->rct_num);

                if ($response['status'] === 'success') {
                    $receipt->update([
                        'status' => 'sent',
                        'last_error' => null
                    ]);
                    $this->info("Successfully sent Receipt #{$receipt->rct_num}");
                } else {
                    $receipt->increment('attempts');
                    $receipt->update(['last_error' => $response['error'] ?? 'Unknown error']);
                    $this->error("Failed to send Receipt #{$receipt->rct_num}: " . ($response['error'] ?? 'API still unreachable'));
                }
            } catch (\Exception $e) {
                $receipt->increment('attempts');
                $receipt->update(['last_error' => $e->getMessage()]);
                $this->error("Exception for Receipt #{$receipt->rct_num}: " . $e->getMessage());
            }
        }

        return 0;
    }
}