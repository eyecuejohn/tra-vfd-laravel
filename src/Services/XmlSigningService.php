<?php

/**
 * Package: eyecuejohn/tra-vfd-laravel
 * Author: John M Kagaruki (john@eyecuemedia.co.tz)
 * License: MIT
 * Copyright: (c) 2025 John M Kagaruki (Eyecuejohn)
 */

namespace Eyecuejohn\TraVfd\Services;

use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use Exception;

class XmlSigningService
{
    /**
     * Path to the TRA .pfx certificate.
     */
    protected string $certPath;

    /**
     * Password for the .pfx certificate.
     */
    protected string $password;

    public function __construct()
    {
        $this->certPath = config('tra-vfd.cert_path');
        $this->password = config('tra-vfd.cert_password');
    }

    /**
     * Sign the TRA XML Payload using RSA-SHA1.
     * * @param string $xmlContent The raw XML to be signed.
     * @return string The signed XML string.
     * @throws Exception
     */
    public function sign(string $xmlContent): string
    {
        // 1. Validate Certificate Existence
        if (!file_exists($this->certPath)) {
            throw new Exception("TRA PFX certificate not found at: {$this->certPath}");
        }

        // 2. Read PKCS12 Certificate
        $pkcs12 = file_get_contents($this->certPath);
        $certs = [];
        
        if (!openssl_pkcs12_read($pkcs12, $certs, $this->password)) {
            throw new Exception("Could not read the TRA .pfx certificate. Please check your TRA_CERT_PASSWORD.");
        }

        // 3. Prepare DOMDocument
        $doc = new \DOMDocument();
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = false;
        $doc->loadXML($xmlContent);

        // 4. Initialize Digital Signature (DSig)
        $objDSig = new XMLSecurityDSig();
        
        // Use Exclusive Canonicalization (C14N) as required by TRA
        $objDSig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);

        // 5. Add Reference with SHA1
        $objDSig->addReference(
            $doc,
            XMLSecurityDSig::SHA1,
            ['http://www.w3.org/2000/09/xmldsig#enveloped-signature'],
            ['force_uri' => true]
        );

        // 6. Initialize Security Key with RSA-SHA1
        $objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, ['type' => 'private']);
        $objKey->loadKey($certs['pkey']);

        // 7. Sign the Document
        $objDSig->sign($objKey);

        // 8. Add X509 Certificate to the KeyInfo section
        $objDSig->add509Cert($certs['cert']);
        
        // 9. Append Signature to the Document Root
        $objDSig->appendSignature($doc->documentElement);

        return $doc->saveXML();
    }
}