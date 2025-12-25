<?php

namespace Eyecuejohn\TraVfd\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TraClient
{
    protected $signer;
    protected $xmlBuilder;
    protected $apiUrl;
    protected $config;

    public function __construct(TraSigner $signer, XmlBuilder $xmlBuilder, array $config)
    {
        $this->signer = $signer;
        $this->xmlBuilder = $xmlBuilder;
        $this->config = $config;
        $this->apiUrl = rtrim($config['api_url'], '/');
    }

    /**
     * Get the Bearer Token from TRA.
     * Tokens are cached to minimize API calls.
     */
    public function getToken()
    {
        return Cache::remember($this->config['token_cache_key'], 3500, function () {
            $response = Http::asForm()->post("{$this->apiUrl}/vfdtoken", [
                'grant_type' => 'client_credentials',
                'client_id' => $this->config['cert_serial'],
                'client_secret' => $this->config['cert_password'],
            ]);

            if ($response->failed()) {
                Log::error("TRA Token Error: " . $response->body());
                throw new \Exception("Could not retrieve TRA Access Token.");
            }

            return $response->json('access_token');
        });
    }

    /**
     * Register VFD (One-time request).
     */
    public function registerVfd(string $certKey)
    {
        $xmlBody = $this->xmlBuilder->buildRegistrationXml($this->config['tin'], $certKey);
        $signature = $this->signer->signXml($xmlBody);

        $response = Http::withHeaders([
            'Cert-Serial' => base64_encode($this->config['cert_serial']),
            'Client' => 'webapi', // Required by TRA
            'Signature' => $signature
        ])
        ->withBody($xmlBody, 'application/xml')
        ->post("{$this->apiUrl}/api/vfdRegReq");

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception("Registration Failed: " . $response->body());
    }

    /**
     * Submit Z-Report (Daily Summary).
     */
    public function submitZReport($date, $dailyTotal, $grossTotal)
    {
        $znum = str_replace('-', '', $date); // YYYYMMDD
        $time = \now()->format('H:i:s');

        $xmlBody = $this->xmlBuilder->buildZReportXml($date, $time, $znum, $dailyTotal, $grossTotal);
        $signature = $this->signer->signXml($xmlBody);

        return $this->transmitToTra($xmlBody, $signature, 'api/efdmszreport');
    }

    /**
     * Submit receipt with immediate attempt and fallback to queue.
     */
    public function submitReceiptWithFallback(array $receiptData)
    {
        // 1. Get sequential numbers safely
        $sequence = $this->getNextSequence();
        
        // 2. Build the XML
        $xmlBody = $this->xmlBuilder->buildReceiptXml($receiptData, $sequence);
        
        // 3. Check for existing queue to ensure strict ordering
        $hasPending = DB::table('tra_vfd_queue')
            ->where('status', 'pending')
            ->exists();

        if ($hasPending) {
            $this->addToQueue($sequence, $xmlBody);
            $this->processQueue(); // Try to flush queue including this new one
            
            return [
                'status' => 'queued',
                'receipt_number' => $sequence['rctnum'],
                'message' => 'Added to queue to preserve sequence order.'
            ];
        }

        // 4. Create the Signature
        $signature = $this->signer->signXml($xmlBody);

        try {
            $response = $this->transmitToTra($xmlBody, $signature);

            if ($response->successful() && $response->json('ACKCODE') == '0') {
                return [
                    'status' => 'success',
                    'receipt_number' => $sequence['rctnum'],
                    'verify_url' => $this->getVerificationQrUrl($response->json('UIN'), $sequence['time']),
                    'data' => $response->json()
                ];
            }
        } catch (\Exception $e) {
            Log::warning("TRA Submission failed, queuing receipt: " . $e->getMessage());
        }

        // 5. Fallback to Queue if API is down or returned error
        $this->addToQueue($sequence, $xmlBody);

        return [
            'status' => 'queued',
            'receipt_number' => $sequence['rctnum'],
            'message' => 'TRA server unreachable. Receipt will be synced automatically.'
        ];
    }

    protected function addToQueue($sequence, $xmlBody)
    {
        DB::table('tra_vfd_queue')->insert([
            'tin' => $this->config['tin'],
            'rct_num' => $sequence['rctnum'],
            'xml_payload' => $xmlBody,
            'status' => 'pending',
            'created_at' => \now(),
        ]);
    }

    /**
     * Transmit the actual request to TRA
     */
    protected function transmitToTra($xml, $signature, $endpoint = 'api/efdmsRctInfo')
    {
        return Http::withToken($this->getToken())
            ->withHeaders([
                'Routing-Key' => $this->config['routing_key'],
                'Cert-Serial' => base64_encode($this->config['cert_serial']),
                'Signature' => $signature,
            ])
            ->withBody($xml, 'application/xml')
            ->post("{$this->apiUrl}/{$endpoint}");
    }

    /**
     * Database-locked sequence management
     */
    protected function getNextSequence()
    {
        return DB::transaction(function () {
            $counter = DB::table('tra_vfd_counters')
                ->where('tin', $this->config['tin'])
                ->lockForUpdate()
                ->first();

            if (!$counter) {
                throw new \Exception("TRA Counters not initialized for this TIN. Run registration first.");
            }

            $nextVal = $counter->rctnum + 1;
            $today = \now()->format('Y-m-d');
            $dc = ($counter->last_rct_date == $today) ? $counter->dc + 1 : 1;

            DB::table('tra_vfd_counters')
                ->where('tin', $this->config['tin'])
                ->update([
                    'rctnum' => $nextVal,
                    'gc' => $nextVal,
                    'dc' => $dc,
                    'last_rct_date' => $today,
                    'znum' => \now()->format('Ymd'),
                ]);

            return [
                'rctnum' => $nextVal,
                'gc' => $nextVal,
                'dc' => $dc,
                'znum' => \now()->format('Ymd'),
                'time' => \now()->format('H:i:s'),
                'date' => $today
            ];
        });
    }

    /**
     * Process one pending item from the queue (Pseudo-cron)
     */
    public function processQueue()
    {
        $pending = DB::table('tra_vfd_queue')
            ->where('status', 'pending')
            ->where('attempts', '<', 5)
            ->first();

        if ($pending) {
            try {
                $signature = $this->signer->signXml($pending->xml_payload);
                $response = $this->transmitToTra($pending->xml_payload, $signature);

                if ($response->successful() && $response->json('ACKCODE') == '0') {
                    DB::table('tra_vfd_queue')->where('id', $pending->id)->update(['status' => 'sent']);
                } else {
                    DB::table('tra_vfd_queue')->where('id', $pending->id)->increment('attempts');
                }
            } catch (\Exception $e) {
                DB::table('tra_vfd_queue')->where('id', $pending->id)->increment('attempts');
            }
        }
    }

    public function getVerificationQrUrl($uin, $time)
    {
        $cleanTime = str_replace(':', '', $time);
        $baseUrl = $this->config['verify_url'];
        
        // If the URL ends with 'Index' or similar, we might need to adjust, 
        // but typically it is Base + / + Code
        return "{$baseUrl}/{$uin}_{$cleanTime}";
    }

    public function generateQrCode($url)
    {
        return QrCode::size(200)->generate($url);
    }
}