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
            Log::error('Notification missing required toSms method');
            return $this->errorResponse('Notification configuration error');
        }

        try {
            $smsData = $notification->toSms($notifiable);
            $message = $smsData['message'] ?? '';
            $phoneNumber = $smsData['to'] ?? null;
            $senderId = $smsData['from'] ?? config('services.backbone_sms.from');

            // Validate required fields
            if (empty($message) || empty($phoneNumber)) {
                throw new \InvalidArgumentException('Missing message or recipient');
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.backbone_sms.token'),
                'Accept' => 'application/json',
            ])
            ->timeout(15)
            ->retry(3, 500)
            ->post(config('services.backbone_sms.url'), [
                'to' => $phoneNumber,
                'message' => $message,
                'from' => $senderId,
            ]);

            $responseData = $response->json();

            if ($response->successful() && ($responseData['status'] ?? null) === 'SUCCESS') {
                return $this->formatSuccessResponse($responseData);
            }

            return $this->handleFailedResponse($response, $responseData);

        } catch (ConnectionException $e) {
            Log::error('SMS gateway connection failed: ' . $e->getMessage());
            return $this->errorResponse('Gateway connection failed', true);
        } catch (\Exception $e) {
            Log::error('SMS processing error: ' . $e->getMessage());
            return $this->errorResponse('Processing error');
        }
    }

    private function formatSuccessResponse(array $responseData): array
    {
        return [
            'success' => true,
            'message_id' => $responseData['msgId'],
            'recipient' => $responseData['to'],
            'cost' => $this->parseMoney($responseData['cost']),
            'balance' => $this->parseMoney($responseData['balance']),
            'status' => $responseData['status'],
            'status_code' => $responseData['statusCode'],
            'description' => $responseData['desc'],
            'raw_response' => $responseData
        ];
    }

    private function handleFailedResponse($response, array $responseData): array
    {
        $errorMessage = $responseData['desc'] ?? 'Unknown gateway error';
        Log::error('SMS delivery failed', [
            'status' => $response->status(),
            'error' => $errorMessage,
            'response' => $responseData
        ]);

        return [
            'success' => false,
            'error' => $errorMessage,
            'status_code' => $responseData['statusCode'] ?? $response->status(),
            'retryable' => $response->serverError()
        ];
    }

    private function errorResponse(string $message, bool $retryable = false): array
    {
        return [
            'success' => false,
            'error' => $message,
            'retryable' => $retryable
        ];
    }

    private function parseMoney(string $value): float
    {
        return (float) preg_replace('/[^0-9.]/', '', $value);
    }
}