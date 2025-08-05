<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Api\SMS;
use App\Http\Requests\StoreSMSRequest;
use App\Http\Requests\UpdateSMSRequest;
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

        $user = auth()->user(); // or get the user by phone/email etc.
        $message = $request->message;
        $phoneNumber = $request->to;

        // Check if balance is sufficient before sending
        if (!$user || !$user->client || $user->client->account_balance < $user->client->cost_per_sms) {
            Log::error("SMS Error: Insufficient balance for user ID {$user->client->id}.");
            return response()->json([
                'message' => 'Insufficient balance to send SMS',
            ], 402);
        }

        // Send notification via the custom SMS channel and capture response
        $notification = new SendSmsNotification($message, $phoneNumber);
        $response = $user->notify($notification);

        // You can return custom response if your channel returns a value
        if (isset($response['status']) && $response['status'] === 'SUCCESS') {

                if (!$user || !$user->client) {
                    Log::error("SMS Error: User or client not found.");
                    return false;
                }

                // Calculate message length and number of SMS parts
                $messageLength = strlen($message);
                $smsCount = ceil($messageLength / 160);
                $cost = $user->client->cost_per_sms * $smsCount;
                $newBalance = $user->client->acount_balance - $cost;

                $user->client->update(['balance' => $newBalance]);

            return response()->json([
                'message' => 'SMS sent successfully',
                'details' => $response
            ], 200);
        }

        return response()->json([
            'message' => 'Failed to send SMS',
            'details' => $response ?? null
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
