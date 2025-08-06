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

        // Assuming you have a User or Notifiable model instance. For example, logged in user:
        $user = Auth::user();

        // Send notification via the custom sms channel
        $user->notify(new SendSmsNotification($message, $phoneNumber));

        return response()->json(['message' => 'SMS sent (or queued) successfully']);
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
