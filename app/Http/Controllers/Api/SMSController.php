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
        try{
            $user->notify(new SendSmsNotification($message, $phoneNumber, $from));
            // Log the SMS details
            Log::info("SMS Sent", [
                'client_id' => $user->client->id,
                'phone' => $phoneNumber,
                'message_length' => $messageLength,
                'sms_parts' => $smsParts,
                'estimated_cost' => $estimatedCost
            ]);
            return response()->json([
                'success' => true,
                'message' => 'SMS sent successfully',
                'to' => $phoneNumber,
                'message_length' => $messageLength,
                'sms_parts' => $smsParts,
                'estimated_cost' => $estimatedCost
            ], 200);

        } catch (\Exception $e) {

            Log::critical("SMS Processing Failed", [
                'client_id' => $user->client->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error while processing SMS',
                'error' => null,
                'error_code' => 'INTERNAL_ERROR'
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
