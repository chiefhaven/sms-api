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
            return false;
        }

        $message = $notification->toSms($notifiable);
        $phoneNumber = $notification->routeNotificationForSms($notifiable);

        if (!$phoneNumber) {
            Log::error("SMS Error: No phone number found for the recipient.");
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

            if (isset($data['status']) && $data['status'] === 'SUCCESS') {

                // Log the successful SMS sending
                Log::info("SMS Sent: " . json_encode($data));
                return $data;

            } else {
                Log::error("SMS Failed: " . $response->body());
                return false;
            }

        } catch (\Exception $e) {
            Log::error("Error sending SMS: " . $e->getMessage());
            return false;
        }
    }

}
