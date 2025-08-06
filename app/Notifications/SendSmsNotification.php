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
    /**
     * Create a new notification instance.
     */
    public function __construct($message, $phoneNumber)
    {
        $this->message = $message;
        $this->phoneNumber = $phoneNumber;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', SmsChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    public function toSms($notifiable)
    {
        // Validate and format the phone number
        $formattedNumber = $this->formatPhoneNumber($this->phoneNumber);

        return [
            'message' => $this->message,
            'to' => $formattedNumber,
        ];
    }

    protected function formatPhoneNumber(string $number): string
    {
        // Remove all non-digit characters
        $cleaned = preg_replace('/[^0-9]/', '', $number);

        // Handle local numbers (assuming Malawi number format)
        if (strlen($cleaned) === 9 && strpos($cleaned, '0') === 0) {
            return '+265' . substr($cleaned, 1);
        }

        // Handle international numbers missing +
        if (strlen($cleaned) > 9 && strpos($cleaned, '265') === 0) {
            return '+' . $cleaned;
        }

        // Return as-is if already properly formatted
        if (strpos($number, '+') === 0) {
            return $number;
        }

        throw new \InvalidArgumentException("Invalid phone number format: {$number}");
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
