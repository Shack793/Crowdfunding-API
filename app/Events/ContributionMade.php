<?php

namespace App\Events;

use App\Models\Contribution;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ContributionMade
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $contribution;
    public $campaign;
    public $campaignOwner;
    public $donorName;

    public function __construct(Contribution $contribution, Campaign $campaign, User $campaignOwner, string $donorName)
    {
        $this->contribution = $contribution;
        $this->campaign = $campaign;
        $this->campaignOwner = $campaignOwner;
        $this->donorName = $donorName;
    }
}
