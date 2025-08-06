<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Bus\Queueable;

class SendSmsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $message;
    protected $phoneNumber;

    public function __construct(string $message, string $phoneNumber)
    {
        $this->message = $message;
        $this->phoneNumber = $phoneNumber;
    }

    public function via($notifiable)
    {
        return ['sms'];
    }

    public function toSms($notifiable)
    {
        return [
            'message' => $this->message,
            'phone' => $this->phoneNumber,
            'client_id' => $notifiable->client->id ?? null
        ];
    }

    public function routeNotificationForSms($notifiable)
    {
        return $this->phoneNumber;
    }
}