<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Api\SMS;
use App\Http\Requests\StoreSMSRequest;
use App\Http\Requests\UpdateSMSRequest;
use App\Notifications\SendSms;
use App\Notifications\SendSmsNotification;
use Illuminate\Http\Request;
use Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SMSController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSMSRequest $request)
    {
        // $sms = SMS::create($request->validated());

        // $sms->send(); // Sends SMS using your logic

        // return response()->json([
        //     'message' => 'SMS sent successfully',
        //     'sms' => $sms,
        // ], 201);
    }

    public function sendSms(Request $request)
    {
        // Validate request with improved phone number validation
        $validated = $request->validate([
            'to' => ['required', 'regex:/^\+[1-9]\d{7,14}$/'], // Enforce + prefix
            'message' => 'required|string|max:1600', // Increased limit for concatenated SMS
            'from' => ['nullable', 'string', 'max:11'] // Sender ID validation
        ]);

        $user = Auth::user();

        // Enhanced user/client validation
        if (!$user?->client) {
            Log::error("SMS Failed: Invalid user/client", [
                'user_id' => $user->id ?? null,
                'ip' => $request->ip()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Account not properly configured',
                'error_code' => 'ACCOUNT_ERROR'
            ], 403);
        }

        // Calculate message metrics
        $messageLength = strlen($validated['message']);
        $smsParts = ceil($messageLength / 153); // 153 chars per part for GSM encoding
        $estimatedCost = $user->client->cost_per_sms * $smsParts;

        // Balance check with logging
        if ($user->client->account_balance < $estimatedCost) {
            Log::warning("SMS Blocked: Insufficient funds", [
                'client_id' => $user->client->id,
                'required' => $estimatedCost,
                'available' => $user->client->account_balance,
                'phone' => substr($validated['to'], -4) // Log last 4 digits only for privacy
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance',
                'required' => $estimatedCost,
                'current_balance' => $user->client->account_balance,
                'error_code' => 'INSUFFICIENT_FUNDS'
            ], 402);
        }

        try {
            // Send notification with queue fallback
            $notification = new SendSmsNotification(
                $validated['message'],
                $validated['to'],
                $validated['from'] ?? $user->client->sender_id
            );

            $gatewayResponse = $user->notify($notification);

            if (!$gatewayResponse || !$gatewayResponse['success']) {
                throw new \RuntimeException($gatewayResponse['error'] ?? 'Unknown gateway error');
            }

            // Process successful response
            $actualCost = $this->calculateActualCost($gatewayResponse, $estimatedCost);
            $newBalance = $user->client->account_balance - $actualCost;

            $user->client->update(['account_balance' => $newBalance]);

            // Create audit log
            SmsLog::create([
                'user_id' => $user->id,
                'client_id' => $user->client->id,
                'message_id' => $gatewayResponse['message_id'],
                'recipient' => $validated['to'],
                'message_parts' => $smsParts,
                'cost' => $actualCost,
                'status' => 'delivered',
                'gateway_response' => $gatewayResponse
            ]);

            return response()->json([
                'success' => true,
                'message' => 'SMS delivered successfully',
                'data' => $this->formatSuccessResponse($validated, $gatewayResponse, $smsParts, $newBalance)
            ]);

        } catch (ValidationException $e) {
            // Special handling for validation errors
            return $this->handleValidationError($e, $request);

        } catch (\Exception $e) {
            // Centralized error handling
            return $this->handleSmsError($e, $user, $request);
        }
    }

    // Helper methods:

    protected function calculateActualCost(array $response, float $estimated): float
    {
        return $response['cost'] ?? $estimated;
    }

    protected function formatSuccessResponse(array $data, array $response, int $parts, float $balance): array
    {
        return [
            'message_id' => $response['message_id'],
            'recipient' => $data['to'],
            'parts' => $parts,
            'cost' => $response['cost'] ?? null,
            'balance' => $balance,
            'gateway_status' => $response['status']
        ];
    }

    protected function handleValidationError(ValidationException $e, Request $request): JsonResponse
    {
        Log::warning("SMS Validation Failed", [
            'errors' => $e->errors(),
            'request' => $request->except(['message'])
        ]);

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
            'error' => $e->getMessage(),
            'request' => $request->except(['message', 'to'])
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Message processing failed',
            'error' => config('app.debug') ? $e->getMessage() : null,
            'error_code' => 'PROCESSING_FAILURE'
        ], 500);
    }

    /**
     * Display the specified resource.
     */
    public function show(SMS $sMS)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SMS $sMS)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSMSRequest $request, SMS $sMS)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SMS $sMS)
    {
        //
    }
}
