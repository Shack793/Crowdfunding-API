<?php

namespace App\Listeners;

use App\Events\ContributionMade;
use App\Notifications\ContributionReceived;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendContributionNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct()
    {
        //
    }

    public function handle(ContributionMade $event)
    {
        // Send notification to campaign owner
        $event->campaignOwner->notify(
            new ContributionReceived(
                $event->contribution,
                $event->campaign,
                $event->donorName
            )
        );
    }
}
