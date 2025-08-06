<?php
namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Auth;

class SmsChannel
{
    public function send($notifiable, Notification $notification)
    {
        if (!method_exists($notification, 'toSms')) {
            return;
        }
        $user = Auth::user() ?? null;
        if (!$user || !$user->client) {
            Log::error("SMS Error: User or client not found for the recipient.");
            return;
        }
        $message = $notification->toSms($notifiable)['message'];
        $phoneNumber = $notification->toSms($notifiable)['to'];
        $from = $notification->toSms($notifiable)['from'];

        if (!$phoneNumber) {
            Log::error("SMS Error: No phone number found for the recipient.");
            return;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.backbone_sms.token'),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])->post(config('services.backbone_sms.url'), [
                'to' => $phoneNumber,
                'message' => $message,
                'from' => $from,
            ]);

            Log::info("SMS Sent: " . $response->body());

            if ($response->successful()) {
                Log::info("SMS Sent: " . $response->body());
            }

            if ($response->failed()) {
                Log::error("SMS Failed: " . $response->body());
            }
        } catch (\Exception $e) {
            Log::error("Error sending SMS: " . $e->getMessage());
        }
    }
}
