<?php
namespace App\Notifications;

use Illuminate\Notifications\Notification;

class SendSmsNotification extends Notification
{
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
        return $this->message;
    }

    public function routeNotificationForSms($notifiable)
    {
        return $this->phoneNumber;
    }
}
