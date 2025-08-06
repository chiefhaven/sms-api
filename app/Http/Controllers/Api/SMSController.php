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
use Illuminate\Support\Facades\Log;

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

        $request->validate([
            'to' => ['required', 'regex:/^\+?[1-9]\d{7,14}$/'],
            'message' => 'required|string|max:160',
        ]);

        $phoneNumber = $request->input('to');
        $message = $request->input('message');
        $from = $request->input('from');

        // Assuming you have a User or Notifiable model instance. For example, logged in user:
        $user = Auth::user();

        // Calculate message metrics
        $messageLength = strlen($message);
        $smsParts = ceil($messageLength / 160);
        $estimatedCost = $user->client->cost_per_sms * $smsParts;
        $currentBalance = $user->client->account_balance;

        // Check account balance
        if ($currentBalance < $estimatedCost) {
            Log::warning("SMS Blocked: Insufficient balance", [
                'client_id' => $user->client->id,
                'required' => $estimatedCost,
                'available' => $currentBalance,
                'phone' => $phoneNumber,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance to send SMS',
                'required_balance' => $estimatedCost,
                'current_balance' => $currentBalance,
                'error_code' => 'INSUFFICIENT_BALANCE'
            ], 402);
        }

        // Send notification via the custom sms channel
        try {
            // Create and send notification
            $notification = new SendSmsNotification($message, $phoneNumber, $from);
            $gatewayResponse = $user->notify($notification);

            // Log successful SMS
            Log::info("SMS Sent Successfully", [
                'client_id' => $user->client->id,
                'phone' => $phoneNumber,
                'message_length' => $messageLength,
                'sms_parts' => $smsParts,
                'estimated_cost' => $estimatedCost,
                'actual_cost' => $gatewayResponse['cost'] ?? null,
                'message_id' => $gatewayResponse['message_id'] ?? null,
                'gateway_status' => $gatewayResponse['status'] ?? null
            ]);

            // Update client balance if needed
            if (isset($gatewayResponse['cost'])) {
                $newBalance = $user->client->account_balance - $gatewayResponse['cost'];
                $user->client->update(['account_balance' => $newBalance]);
            }

            return response()->json([
                'success' => true,
                'message' => 'SMS sent successfully',
                'data' => [
                    'recipient' => $phoneNumber,
                    'message_id' => $gatewayResponse['message_id'] ?? null,
                    'message_length' => $messageLength,
                    'sms_parts' => $smsParts,
                    'estimated_cost' => $estimatedCost,
                    'actual_cost' => $gatewayResponse['cost'] ?? null,
                    'gateway_status' => $gatewayResponse['status'] ?? null,
                    'new_balance' => $newBalance ?? null
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning("SMS Validation Failed", [
                'errors' => $e->errors(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'error_code' => 'VALIDATION_ERROR'
            ], 422);

        } catch (\Exception $e) {
            Log::critical("SMS Processing Failed", [
                'client_id' => $user->client->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->except(['message']) // Exclude sensitive data
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process SMS',
                'error' => config('app.debug') ? $e->getMessage() : null,
                'error_code' => 'PROCESSING_ERROR'
            ], 500);
        }
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
