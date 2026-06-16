<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SmsService
{
    protected string $apiUrl;
    protected string $apiKey;
    protected string $senderId;

    public function __construct()
    {
        $this->apiUrl   = 'https://sms-api.ipsova.com/api/v3/sms/send';
        $this->apiKey   = config('app.sms_api_key');
        $this->senderId = 'AUTOSALE'; 
    }

    public function send(string $fullPhoneNumber, string $message): bool
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type'  => 'application/json',
        ])->post($this->apiUrl, [
            'recipient' => $fullPhoneNumber,
            'sender_id' => $this->senderId,
            'type'      => 'plain',
            'message'   => $message,
        ]);

        return $response->successful();
    }
}
