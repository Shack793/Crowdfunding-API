<?php

namespace App\Notifications;

use App\Models\Campaign;
use App\Models\Contribution;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContributionReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public $contribution;
    public $campaign;
    public $donorName;

    public function __construct(Contribution $contribution, Campaign $campaign, string $donorName)
    {
        $this->contribution = $contribution;
        $this->campaign = $campaign;
        $this->donorName = $donorName;
    }

    public function via($notifiable)
    {
        return ['database', 'mail']; // Re-enabled mail channel with log driver
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'contribution_received',
            'title' => 'New Contribution Received',
            'message' => "{$this->donorName} contributed \${$this->contribution->amount} to your campaign '{$this->campaign->title}'",
            'data' => [
                'contribution_id' => $this->contribution->id,
                'campaign_id' => $this->campaign->id,
                'campaign_title' => $this->campaign->title,
                'amount' => $this->contribution->amount,
                'donor_name' => $this->donorName,
                'created_at' => now()
            ]
        ];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('New Contribution Received - ' . $this->campaign->title)
            ->greeting('Hello ' . $notifiable->name . '!')
            ->line("{$this->donorName} has made a contribution to your campaign.")
            ->line("Campaign: {$this->campaign->title}")
            ->line("Amount: \${$this->contribution->amount}")
            ->action('View Campaign', url('/campaigns/' . $this->campaign->id))
            ->line('Thank you for using our crowdfunding platform!');
    }
}
