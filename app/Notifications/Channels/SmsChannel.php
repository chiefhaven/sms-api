<?php
namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Auth;
use Illuminate\Http\Client\ConnectionException;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
            $startTime = microtime(true);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.backbone_sms.token'),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->timeout(15) // 15 second timeout
            ->retry(3, 500, function ($exception) {
                // Don't retry if it's a 4xx client error
                return !($exception instanceof HttpException && $exception->getCode() >= 400 && $exception->getCode() < 500);
            })
            ->post(config('services.backbone_sms.url'), [
                'to' => $phoneNumber,
                'message' => $message,
                'from' => $from,
            ]);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2); // in ms
            $responseData = $response->json();

            if ($response->successful()) {
                Log::info("SMS Delivered Successfully", [
                    'message_id' => $responseData['msgId'] ?? null,
                    'recipient' => substr($phoneNumber, -4), // Last 4 digits for privacy
                    'cost' => $responseData['cost'] ?? null,
                    'status' => $responseData['status'] ?? null,
                    'response_time_ms' => $responseTime,
                    'gateway' => 'backbone'
                ]);

                return [
                    'success' => true,
                    'message_id' => $responseData['msgId'],
                    'cost' => $this->parseMoney($responseData['cost']),
                    'status' => $responseData['status'],
                    'raw_response' => $responseData
                ];
            }

            // Handle specific HTTP errors
            $errorMessage = match($response->status()) {
                401 => 'Invalid API credentials',
                402 => 'Payment required',
                403 => 'Forbidden',
                404 => 'API endpoint not found',
                429 => 'Rate limit exceeded',
                500 => 'Gateway server error',
                default => $responseData['desc'] ?? 'Unknown gateway error'
            };

            Log::error("SMS Delivery Failed", [
                'status_code' => $response->status(),
                'error' => $errorMessage,
                'response' => $responseData,
                'response_time_ms' => $responseTime
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
                'status_code' => $response->status(),
                'retryable' => $response->serverError() // Can retry on 5xx errors
            ];

        } catch (ConnectionException $e) {
            Log::critical("SMS Gateway Connection Failed", [
                'error' => $e->getMessage(),
                'phone' => substr($phoneNumber, -4)
            ]);

            return [
                'success' => false,
                'error' => 'Gateway connection failed',
                'retryable' => true
            ];

        } catch (\Exception $e) {
            Log::error("SMS Processing Exception", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Processing error'
            ];
        }


    }

    // Helper method to parse money values
    private function parseMoney(string $value): float
    {
        return (float) preg_replace('/[^0-9.]/', '', $value);
    }
}
