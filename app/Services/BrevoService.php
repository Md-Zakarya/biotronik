<?php

namespace App\Services;

use Brevo\Client\Configuration;
use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Model\SendSmtpEmail;
use Illuminate\Support\Facades\Log;

class BrevoService
{
    protected $apiInstance;

    public function __construct()
    {
        $config = Configuration::getDefaultConfiguration()
            ->setApiKey('api-key', env('BREVO_API_KEY'));
            
        $this->apiInstance = new TransactionalEmailsApi(
            new \GuzzleHttp\Client(),
            $config
        );
    }

    public function sendOTP($email, $name, $otp)
    {
        Log::info('Sending OTP', ['email' => $email, 'name' => $name, 'otp' => $otp]);

        $sendSmtpEmail = new SendSmtpEmail([
            'subject' => 'Your OTP Code',
            'sender' => [
                'name' => env('MAIL_FROM_NAME', 'Your Company'),
                'email' => env('MAIL_FROM_ADDRESS', 'no-reply@yourcompany.com'),
            ],
            'replyTo' => ['name' => 'Support', 'email' => 'support@yourcompany.com'],
            'to' => [['name' => $name, 'email' => $email]],
            'htmlContent' => '<html><body><h1>Your OTP Code is {{params.otp}}</h1></body></html>',
            'params' => ['otp' => $otp]
        ]);

        try {
            $response = $this->apiInstance->sendTransacEmail($sendSmtpEmail);
            Log::info('OTP sent successfully', [
                'response' => $response,
                'messageId' => $response->getMessageId(),
                'email' => $email,
                'name' => $name,
                'otp' => $otp
            ]);
            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to send OTP', [
                'error' => $e->getMessage(),
                'email' => $email,
                'name' => $name,
                'otp' => $otp
            ]);
            throw $e;
        }
    }
}