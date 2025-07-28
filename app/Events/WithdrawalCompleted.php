<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WithdrawalCompleted
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $amount;
    public $transactionId;
    public $status;

    public function __construct(User $user, float $amount, string $transactionId, string $status)
    {
        $this->user = $user;
        $this->amount = $amount;
        $this->transactionId = $transactionId;
        $this->status = $status;
    }
}
