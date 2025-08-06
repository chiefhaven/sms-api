<?php

namespace App\Notifications;

use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SendSms extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected string $message,
        protected string $phoneNumber
    ) {}

    public function via($notifiable)
    {
        return [SmsChannel::class];
    }

    public function toSms($notifiable)
    {
        return [
            'message' => $this->message,
            'phone' => $this->phoneNumber,
            'sender_id' => $notifiable->client->sender_id ?? config('services.backbone_sms.from')
        ];
    }

    public function routeNotificationForSms($notifiable)
    {
        return $this->phoneNumber;
    }
}