<?php
namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Client\ConnectionException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class SmsChannel
{
    public function send($notifiable, Notification $notification)
    {
        if (!method_exists($notification, 'toSms')) {
            Log::error('Notification missing required toSms method', [
                'notification' => get_class($notification)
            ]);
            return false;
        }

        $user = Auth::user();
        if (!$user || !$user->client) {
            Log::error('SMS failed: Invalid user or client', [
                'user_id' => $user->id ?? null
            ]);
            return false;
        }

        try {
            $smsData = $notification->toSms($notifiable);
            $message = $smsData['message'] ?? '';
            $phoneNumber = $smsData['to'] ?? null;
            $senderId = $smsData['from'] ?? $user->client->sender_id ?? config('services.backbone_sms.from');

            if (empty($message) || empty($phoneNumber)) {
                throw new \InvalidArgumentException('Missing message content or recipient');
            }

            $startTime = microtime(true);
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.backbone_sms.token'),
                'Accept' => 'application/json',
            ])
            ->timeout(15)
            ->retry(3, 500, function ($exception) {
                return !($exception instanceof HttpException && $exception->getCode() < 500);
            })
            ->post(config('services.backbone_sms.url'), [
                'to' => $phoneNumber,
                'message' => $message,
                'from' => $senderId,
            ]);

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            $responseData = $response->json();

            if ($response->successful() && ($responseData['status'] ?? null) === 'SUCCESS') {
                return [
                    'success' => true,
                    'message_id' => $responseData['msgId'],
                    'cost' => $this->parseMoney($responseData['cost']),
                    'status' => $responseData['status'],
                    'raw_response' => $responseData
                ];
            }

            $errorMessage = $this->getErrorMessage($response, $responseData);
            throw new \RuntimeException($errorMessage);

        } catch (ConnectionException $e) {
            Log::critical('SMS gateway connection failed', ['error' => $e->getMessage()]);
            return false;
        } catch (\Exception $e) {
            Log::error('SMS processing failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function parseMoney(string $value): float
    {
        return (float) preg_replace('/[^0-9.]/', '', $value);
    }

    private function getErrorMessage($response, $responseData): string
    {
        return match($response->status()) {
            401 => 'Invalid API credentials',
            402 => 'Payment required',
            403 => 'Forbidden',
            404 => 'API endpoint not found',
            429 => 'Rate limit exceeded',
            500 => 'Gateway server error',
            default => $responseData['desc'] ?? 'Unknown gateway error'
        };
    }
}