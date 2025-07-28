<?php

namespace App\Listeners;

use App\Events\WithdrawalCompleted;
use App\Notifications\WithdrawalProcessed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendWithdrawalNotification implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct()
    {
        //
    }

    public function handle(WithdrawalCompleted $event)
    {
        // Send notification to the user who made the withdrawal
        $event->user->notify(
            new WithdrawalProcessed(
                $event->amount,
                $event->transactionId,
                $event->status
            )
        );
    }
}
