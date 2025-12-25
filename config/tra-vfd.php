<?php

/**
 * Package: eyecuejohn/tra-vfd-laravel
 * Author: John M Kagaruki (john@eyecuemedia.co.tz)
 * License: MIT
 * Copyright: (c) 2025 John M Kagaruki (Eyecuejohn)
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Legal Entity Details
    |--------------------------------------------------------------------------
    | These details appear at the top of every Fiscal Receipt.
    */
    'company_name' => env('TRA_COMPANY_NAME', 'YOUR_COMPANY_NAME'),
    'address'      => env('TRA_COMPANY_ADDRESS', 'YOUR_PO_BOX_AND_CITY'),
    'mobile'       => env('TRA_COMPANY_MOBILE', '255000000000'),
    'tax_office'   => env('TRA_TAX_OFFICE', 'Name of Tax Office'),

    /*
    |--------------------------------------------------------------------------
    | TRA VFD Credentials
    |--------------------------------------------------------------------------
    */
    'tin'            => env('TRA_TIN', '123456789'),
    'vrn'            => env('TRA_VRN', 'NOT REGISTERED'),
    'cert_serial'    => env('TRA_CERT_SERIAL'),
    'cert_password'  => env('TRA_CERT_PASSWORD'),
    'routing_key'    => env('TRA_ROUTING_KEY'),
    
    /*
    |--------------------------------------------------------------------------
    | API Endpoints
    |--------------------------------------------------------------------------
    */
    'api_url'    => env('TRA_API_URL', 'https://vfd.tra.go.tz'),
    'verify_url' => env('TRA_VERIFY_URL', 'https://verify.tra.go.tz'),
    
    /*
    |--------------------------------------------------------------------------
    | Certificate Settings
    |--------------------------------------------------------------------------
    */
    'cert_path' => storage_path('app/tra/cert.pfx'),

    /*
    |--------------------------------------------------------------------------
    | Receipt Defaults
    |--------------------------------------------------------------------------
    */
    'footer_text' => env('TRA_RECEIPT_FOOTER', 'Thank you for paying tax!'),
];