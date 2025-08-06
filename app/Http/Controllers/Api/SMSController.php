<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmsLog;
use App\Notifications\SendSmsNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SMSController extends Controller
{
    public function sendSms(Request $request)
    {
        $validated = $this->validateRequest($request);
        $user = Auth::user();

        if (!$user?->client) {
            return $this->errorResponse('Account not properly configured', 'ACCOUNT_ERROR', 403);
        }

        $messageMetrics = $this->calculateMessageMetrics($validated['message'], $user->client->cost_per_sms);

        if ($user->client->account_balance < $messageMetrics['estimatedCost']) {
            return $this->insufficientBalanceResponse($user, $messageMetrics['estimatedCost'], $validated['to']);
        }

        try {
            $notification = new SendSmsNotification(
                $validated['message'],
                $validated['to'],
                $validated['from'] ?? $user->client->sender_id
            );

            $gatewayResponse = $user->notify($notification);

            if (!$this->isValidGatewayResponse($gatewayResponse)) {
                return $this->gatewayErrorResponse($user, $gatewayResponse);
            }

            return $this->processSuccessfulResponse($user, $validated, $gatewayResponse, $messageMetrics);

        } catch (ValidationException $e) {
            return $this->handleValidationError($e, $request);
        } catch (\Exception $e) {
            return $this->handleSmsError($e, $user, $request);
        }
    }

    // Helper Methods

    private function validateRequest(Request $request): array
    {
        return $request->validate([
            'to' => ['required', 'regex:/^\+[1-9]\d{7,14}$/'],
            'message' => 'required|string|max:1600',
            'from' => ['nullable', 'string', 'max:11']
        ]);
    }

    private function calculateMessageMetrics(string $message, float $costPerSms): array
    {
        $messageLength = strlen($message);
        $smsParts = ceil($messageLength / 153);
        return [
            'messageLength' => $messageLength,
            'smsParts' => $smsParts,
            'estimatedCost' => $costPerSms * $smsParts
        ];
    }

    private function insufficientBalanceResponse($user, float $required, string $phone): JsonResponse
    {
        Log::warning("SMS Blocked: Insufficient funds", [
            'client_id' => $user->client->id,
            'required' => $required,
            'available' => $user->client->account_balance,
            'phone' => substr($phone, -4)
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Insufficient balance',
            'required' => $required,
            'current_balance' => $user->client->account_balance,
            'error_code' => 'INSUFFICIENT_FUNDS'
        ], 402);
    }

    private function processSuccessfulResponse($user, array $validated, array $response, array $metrics): JsonResponse
    {
        $actualCost = $response['cost'] ?? $metrics['estimatedCost'];
        $newBalance = $user->client->account_balance - $actualCost;

        DB::transaction(function () use ($user, $newBalance, $response, $validated, $metrics, $actualCost) {
            $user->client->update(['account_balance' => $newBalance]);

            SmsLog::create([
                'user_id' => $user->id,
                'client_id' => $user->client->id,
                'message_id' => $response['message_id'],
                'recipient' => $validated['to'],
                'message' => $validated['message'],
                'message_parts' => $metrics['smsParts'],
                'cost' => $actualCost,
                'new_balance' => $newBalance,
                'status' => 'delivered',
                'gateway_status' => $response['status'] ?? null,
                'gateway_response' => $response
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'SMS delivered successfully',
            'data' => [
                'message_id' => $response['message_id'],
                'recipient' => $validated['to'],
                'parts' => $metrics['smsParts'],
                'cost' => $actualCost,
                'balance' => $newBalance,
                'gateway_status' => $response['status'] ?? null
            ]
        ]);
    }

    private function isValidGatewayResponse($response): bool
    {
        return is_array($response)
            && ($response['success'] ?? false)
            && isset($response['message_id']);
    }

    private function gatewayErrorResponse($user, $response): JsonResponse
    {
        Log::error("SMS Failed: Invalid gateway response", [
            'user_id' => $user->id,
            'client_id' => $user->client->id,
            'response' => $response
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Invalid response from SMS gateway',
            'error_code' => 'GATEWAY_ERROR'
        ], 502);
    }

    private function errorResponse(string $message, string $code, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error_code' => $code
        ], $status);
    }

    protected function handleValidationError(ValidationException $e, Request $request): JsonResponse
    {
        Log::warning("SMS Validation Failed", $e->errors());
        return response()->json([
            'success' => false,
            'message' => 'Invalid request data',
            'errors' => $e->errors(),
            'error_code' => 'VALIDATION_FAILED'
        ], 422);
    }

    protected function handleSmsError(\Exception $e, $user, Request $request): JsonResponse
    {
        Log::critical("SMS Processing Error", [
            'client_id' => $user->client->id ?? null,
            'error' => $e->getMessage()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Message processing failed',
            'error' => config('app.debug') ? $e->getMessage() : null,
            'error_code' => 'PROCESSING_FAILURE'
        ], 500);
    }
}