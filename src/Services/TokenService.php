<?php

/**
 * Package: eyecuejohn/tra-vfd-laravel
 * Author: John M Kagaruki (john@eyecuemedia.co.tz)
 * License: MIT
 * Copyright: (c) 2025 John M Kagaruki (Eyecuejohn)
 */

namespace Eyecuejohn\TraVfd\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class TokenService
{
    /**
     * The package configuration.
     *
     * @var array
     */
    protected $config;

    public function __construct()
    {
        $this->config = config('tra-vfd');
    }

    /**
     * Get a valid Bearer Token from TRA.
     * Uses Laravel Cache to minimize redundant API requests.
     *
     * @return string|null
     */
    public function getToken(): ?string
    {
        $cacheKey = 'tra_vfd_token_' . $this->config['tin'];

        return Cache::remember($cacheKey, 300, function () {
            $response = Http::asForm()->post($this->config['api_url'] . '/token', [
                'grant_type' => 'password',
                'username'   => $this->config['tin'],
                'password'   => $this->config['cert_password'],
            ]);

            if ($response->successful()) {
                return $response->json()['access_token'] ?? null;
            }

            return null;
        });
    }

    /**
     * Forcefully clear the cached token (useful for debugging).
     */
    public function clearTokenCache(): void
    {
        Cache::forget('tra_vfd_token_' . $this->config['tin']);
    }
}