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

            Log::warning('SMS gateway responded with failure', $responseData);
            return $this->handleFailedResponse($response, $responseData);

        } catch (ConnectionException $e) {
            Log::error('SMS gateway connection failed: ' . $e->getMessage());
            return $this->errorResponse('Gateway connection failed', true);
        } catch (\Exception $e) {
            Log::error('SMS processing error: ' . $e->getMessage());
            return $this->errorResponse('Processing error');
        }
    }

    protected function errorResponse($message, $connectionError = false)
    {
        return [
            'status' => 'ERROR',
            'message' => $message,
            'connection_error' => $connectionError,
        ];
    }

    protected function formatSuccessResponse(array $data)
    {
        return [
            'status' => $data['status'] ?? 'SUCCESS',
            'message_id' => $data['message_id'] ?? null,
            'recipient' => $data['to'] ?? null,
            'cost' => $data['cost'] ?? 0,
            'raw_response' => $data,
        ];
    }

    protected function handleFailedResponse($response, array $data)
    {
        return [
            'status' => $data['status'] ?? 'FAILED',
            'message' => $data['message'] ?? 'Unknown failure',
            'raw_response' => $data,
        ];
    }
}