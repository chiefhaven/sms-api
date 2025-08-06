<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    public function send($notifiable, Notification $notification)
    {
        if (!method_exists($notification, 'toSms')) {
            Log::error("SMS Channel Error: Notification missing toSms method");
            throw new \RuntimeException('Notification is missing toSms method');
        }

        $smsData = $notification->toSms($notifiable);
        $message = $smsData['message'] ?? '';
        $phoneNumber = $smsData['phone'] ?? $notification->routeNotificationForSms($notifiable);

        if (empty($phoneNumber)) {
            Log::error("SMS Error: No valid phone number provided", [
                'notifiable' => $notifiable->id ?? null,
                'notification' => get_class($notification)
            ]);
            return false;
        }

        // Validate phone number format
        if (!preg_match('/^\+?[1-9]\d{7,14}$/', $phoneNumber)) {
            Log::error("SMS Error: Invalid phone number format", [
                'phone' => $phoneNumber
            ]);
            return false;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.backbone_sms.token'),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post(config('services.backbone_sms.url'), [
                'to' => $phoneNumber,
                'message' => $message,
                'from' => config('services.backbone_sms.from'),
            ]);

            $data = $response->json();

            if ($response->successful() && isset($data['status']) && $data['status'] === 'SUCCESS') {
                // Format the successful response consistently
                return [
                    'success' => true,
                    'message_id' => $data['msgId'],
                    'cost' => (float) str_replace(['MWK', ' '], '', $data['cost']),
                    'balance' => (float) str_replace(['MWK', ' '], '', $data['balance']),
                    'recipient' => $data['to'],
                    'status' => $data['status'],
                    'status_code' => $data['statusCode'],
                    'raw_response' => $data // Keep original response for reference
                ];
            }

            // Handle failed responses
            return [
                'success' => false,
                'error' => $data['desc'] ?? 'Unknown error',
                'status_code' => $data['statusCode'] ?? '500',
                'raw_response' => $data
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'status_code' => '500'
            ];
        }
    }
}