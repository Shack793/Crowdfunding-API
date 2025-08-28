<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WithdrawalEmailVerification extends Notification implements ShouldQueue
{
    use Queueable;

    public $verificationCode;
    public $expiresInMinutes;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $verificationCode, int $expiresInMinutes = 15)
    {
        $this->verificationCode = $verificationCode;
        $this->expiresInMinutes = $expiresInMinutes;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Withdrawal Verification Code - ' . config('app.name'))
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line('You have requested to withdraw funds from your account.')
            ->line('To complete this withdrawal, please use the verification code below:')
            ->line('')
            ->line('**Verification Code: ' . $this->verificationCode . '**')
            ->line('')
            ->line('This code will expire in ' . $this->expiresInMinutes . ' minutes.')
            ->line('For your security, please do not share this code with anyone.')
            ->line('If you did not request this withdrawal, please contact our support team immediately.')
            ->line('')
            ->line('Thank you for using ' . config('app.name') . '!')
            ->salutation('Best regards,')
            ->salutation('The ' . config('app.name') . ' Team');
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'withdrawal_verification',
            'title' => 'Withdrawal Verification Code Sent',
            'message' => 'A verification code has been sent to your email for withdrawal request.',
            'data' => [
                'verification_code_sent' => true,
                'expires_in_minutes' => $this->expiresInMinutes,
                'timestamp' => now()->toISOString(),
            ]
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'withdrawal_verification',
            'verification_code' => $this->verificationCode,
            'expires_in_minutes' => $this->expiresInMinutes,
        ];
    }
}
