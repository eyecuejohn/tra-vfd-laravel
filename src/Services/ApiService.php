<?php

/**
 * Package: eyecuejohn/tra-vfd-laravel
 * Author: John M Kagaruki (john@eyecuemedia.co.tz)
 * License: MIT
 * Copyright: (c) 2025 John M Kagaruki (Eyecuejohn)
 */

namespace Eyecuejohn\TraVfd\Services;

use Eyecuejohn\TraVfd\Models\TraVfdQueue;
use Illuminate\Support\Facades\Http;
use Exception;

class ApiService
{
    protected $tokenService;
    protected $config;

    public function __construct(TokenService $tokenService)
    {
        $this->tokenService = $tokenService;
        $this->config = config('tra-vfd');
    }

    /**
     * Submit the signed XML to TRA.
     * If the API is down, it automatically saves to the queue.
     */
    public function submitReceipt(string $xmlPayload, string $rctNum): array
    {
        $token = $this->tokenService->getToken();

        if (!$token) {
            return $this->addToQueue($xmlPayload, $rctNum, 'Could not retrieve API token.');
        }

        try {
            $response = Http::withToken($token)
                ->withHeaders(['Routing-Key' => $this->config['routing_key']])
                ->withBody($xmlPayload, 'application/xml')
                ->post($this->config['api_url'] . '/api/v1/receipts');

            if ($response->successful()) {
                return [
                    'status' => 'success',
                    'data' => $response->json(),
                    'queued' => false
                ];
            }

            // If API returns an error but is reachable (e.g., 500), queue it
            return $this->addToQueue($xmlPayload, $rctNum, "API Error: " . $response->body());

        } catch (Exception $e) {
            // If API is totally unreachable (timeout/dns), queue it
            return $this->addToQueue($xmlPayload, $rctNum, $e->getMessage());
        }
    }

    /**
     * Internal helper to handle the fallback logic.
     */
    protected function addToQueue(string $xmlPayload, string $rctNum, string $error): array
    {
        TraVfdQueue::create([
            'tin' => $this->config['tin'],
            'rct_num' => $rctNum,
            'xml_payload' => $xmlPayload,
            'status' => 'pending',
            'last_error' => $error
        ]);

        return [
            'status' => 'queued',
            'message' => 'TRA API unreachable. Receipt saved to offline queue.',
            'error' => $error,
            'queued' => true
        ];
    }
}