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
        // Validate notification has required method
        if (!method_exists($notification, 'toSms')) {
            Log::error('SMS Notification missing toSms method', [
                'notification' => get_class($notification),
                'notifiable' => get_class($notifiable)
            ]);
            return false;
        }

        // Get authenticated user
        $user = Auth::user();
        if (!$user || !$user->client) {
            Log::error('SMS Error: Invalid user or client', [
                'user_id' => $user->id ?? null
            ]);
            return false;
        }

        try {
            // Get SMS data from notification
            $smsData = $notification->toSms($notifiable);
            $message = $smsData['message'] ?? '';
            $phoneNumber = $smsData['to'] ?? null;
            $senderId = $smsData['from'] ?? config('services.backbone_sms.from');

            // Validate required fields
            if (empty($message) || empty($phoneNumber)) {
                Log::error('SMS Error: Missing message or recipient', [
                    'has_message' => !empty($message),
                    'has_recipient' => !empty($phoneNumber)
                ]);
                return false;
            }

            // Send SMS via Backbone API
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.backbone_sms.token'),
                'Accept' => 'application/json',
            ])
            ->timeout(15) // 15 second timeout
            ->retry(3, 500) // Retry 3 times with 500ms delay
            ->post(config('services.backbone_sms.url'), [
                'to' => $phoneNumber,
                'message' => $message,
                'from' => $senderId,
            ]);

            // Process response
            $responseData = $response->json();
            $isSuccessful = $response->successful() &&
                        ($responseData['status'] ?? null) === 'SUCCESS';

            if ($isSuccessful) {
                // Calculate message metrics
                $messageLength = strlen($message);
                $smsParts = ceil($messageLength / 160);
                $gatewayCost = $this->parseMoney($responseData['cost'] ?? '0');
                $actualCost = $gatewayCost > 0 ? $gatewayCost : ($user->client->cost_per_sms * $smsParts);
                $newBalance = $user->client->account_balance - $actualCost;

                // Update client balance
                $user->client->update(['account_balance' => $newBalance]);

                // Prepare response
                $result = [
                    'success' => true,
                    'message_id' => $responseData['msgId'] ?? null,
                    'recipient' => $responseData['to'] ?? $phoneNumber,
                    'message_length' => $messageLength,
                    'sms_parts' => $smsParts,
                    'cost' => $actualCost,
                    'new_balance' => $newBalance,
                    'status' => $responseData['status'] ?? null,
                    'status_code' => $responseData['statusCode'] ?? null,
                    'gateway_response' => $responseData
                ];

                Log::info('SMS Delivered Successfully', $result);
                return $result;
            }

            // Handle failed response
            $errorMessage = $responseData['desc'] ?? 'Unknown gateway error';
            Log::error('SMS Delivery Failed', [
                'error' => $errorMessage,
                'status_code' => $responseData['statusCode'] ?? null,
                'response' => $responseData
            ]);
            return false;

        } catch (\Exception $e) {
            Log::critical('SMS Sending Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Parse money values from gateway (e.g. "MWK 14.0" => 14.0)
     */
    protected function parseMoney(string $value): float
    {
        return (float) preg_replace('/[^0-9.]/', '', $value);
    }
}
