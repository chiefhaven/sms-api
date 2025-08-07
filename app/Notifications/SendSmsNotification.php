<?php

namespace App\Notifications;

use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendSmsNotification extends Notification
{
    use Queueable;

    protected string $message;
    protected string $phoneNumber;
    protected string $from;

    public function __construct($message, $phoneNumber, $from)
    {
        $this->message = $message;
        $this->phoneNumber = $phoneNumber;
        $this->from = $from;
    }

    public function via(object $notifiable): array
    {
        return [SmsChannel::class];
    }

    public function toSms($notifiable): array
    {
        $formattedNumber = $this->formatPhoneNumber($this->phoneNumber);

        return [
            'message' => $this->message,
            'from' => $this->from,
            'to' => $formattedNumber,
        ];
    }

    protected function formatPhoneNumber(string $number): string
    {
        $cleaned = preg_replace('/[^0-9]/', '', $number);

        // If number starts with 0 and is 10 digits, convert to +265 format
        if (strlen($cleaned) === 10 && strpos($cleaned, '0') === 0) {
            return '+265' . substr($cleaned, 1);
        }

        // If number starts with 265 and is 12 digits, convert to + format
        if (strlen($cleaned) === 12 && strpos($cleaned, '265') === 0) {
            return '+' . $cleaned;
        }

        // If number already has a + and is valid length, return as-is
        if (strpos($number, '+') === 0) {
            return $number;
        }

        throw new \InvalidArgumentException("Invalid phone number format: {$number}");
    }

    public function toArray(object $notifiable): array
    {
        return [];
    }
}
