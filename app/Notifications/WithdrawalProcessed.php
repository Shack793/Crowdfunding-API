<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WithdrawalProcessed extends Notification implements ShouldQueue
{
    use Queueable;

    public $amount;
    public $transactionId;
    public $status;

    public function __construct(float $amount, string $transactionId, string $status)
    {
        $this->amount = $amount;
        $this->transactionId = $transactionId;
        $this->status = $status;
    }

    public function via($notifiable)
    {
        return ['database', 'mail']; // Re-enabled mail channel with log driver
    }

    public function toDatabase($notifiable)
    {
        $statusMessage = $this->status === 'completed' 
            ? 'has been successfully processed'
            : 'is being processed';

        return [
            'type' => 'withdrawal_processed',
            'title' => 'Withdrawal ' . ucfirst($this->status),
            'message' => "Your withdrawal of \${$this->amount} {$statusMessage}",
            'data' => [
                'amount' => $this->amount,
                'transaction_id' => $this->transactionId,
                'status' => $this->status,
                'processed_at' => now()
            ]
        ];
    }

    public function toMail($notifiable)
    {
        $subject = $this->status === 'completed' 
            ? 'Withdrawal Completed'
            : 'Withdrawal Processing';

        $statusText = $this->status === 'completed'
            ? 'has been successfully completed'
            : 'is currently being processed';

        return (new MailMessage)
            ->subject($subject . ' - $' . $this->amount)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line("Your withdrawal request of \${$this->amount} {$statusText}.")
            ->line("Transaction ID: {$this->transactionId}")
            ->line($this->status === 'completed' 
                ? 'The funds should appear in your account within 1-3 business days.'
                : 'We will notify you once the withdrawal is completed.')
            ->line('Thank you for using our platform!');
    }
}
