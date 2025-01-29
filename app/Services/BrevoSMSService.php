<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BrevoSMSService
{
    protected $apiKey;
    protected $sender;
    protected $baseUrl = 'https://api.brevo.com/v3/transactionalSMS/sms';

    public function __construct()
    {
        $this->apiKey = config('services.brevo.key');
        $this->sender = config('services.brevo.sms_sender');
    }

    public function sendOTP($phone, $otp)
    {
        return $this->sendSMS($phone, "Your OTP is: $otp", 'transactional', 'otp_verification');
    }

    public function sendMarketingSMS($phone, $message, $tag = null)
    {
        return $this->sendSMS($phone, $message, 'marketing', $tag);
    }

    protected function sendSMS($phone, $message, $type = 'transactional', $tag = null, $callbackUrl = null)
    {
        try {
            $payload = [
                'type' => $type,
                'unicodeEnabled' => true,
                'sender' => $this->sender,
                'recipient' => $phone,
                'content' => $message,
            ];

            if ($tag) {
                $payload['tag'] = $tag;
            }

            if ($callbackUrl) {
                $payload['webUrl'] = $callbackUrl;
            }

            $response = Http::withHeaders([
                'accept' => 'application/json',
                'api-key' => $this->apiKey,
                'content-type' => 'application/json',
            ])->post($this->baseUrl, $payload);

            if ($response->successful()) {
                Log::info('SMS sent successfully', [
                    'phone' => $phone,
                    'type' => $type
                ]);
                return $response->json();
            }

            throw new \Exception($response->body());
        } catch (\Exception $e) {
            Log::error('Failed to send SMS', [
                'error' => $e->getMessage(),
                'phone' => $phone,
                'type' => $type
            ]);
            throw $e;
        }
    }
}