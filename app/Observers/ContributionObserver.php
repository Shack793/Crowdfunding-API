<?php

namespace App\Observers;

use App\Models\Contribution;
use Illuminate\Support\Facades\Log;

class ContributionObserver
{
    public function created(Contribution $contribution)
    {
        $campaign = $contribution->campaign;
        if ($campaign) {
            $campaign->increment('current_amount', $contribution->amount);
            Log::info('Campaign current_amount updated via observer', [
                'campaign_id' => $campaign->id,
                'new_current_amount' => $campaign->fresh()->current_amount
            ]);
        }
    }
}
