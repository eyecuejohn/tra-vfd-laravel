<?php

namespace Eyecuejohn\TraVfd\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Eyecuejohn\TraVfd\Services\TraClient
 * * @method static array submitReceiptWithFallback(array $receiptData)
 * @method static string getVerificationQrUrl(string $verificationCode, string $time)
 * @method static string generateQrCode(string $url)
 * @method static bool registerDevice()
 */
class TraVfd extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * This string MUST match the key you use when binding 
     * the service in your TraVfdServiceProvider.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'tra-vfd';
    }
}