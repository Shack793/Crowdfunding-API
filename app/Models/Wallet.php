<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $casts = [
        'balance' => 'decimal:2',
        'total_withdrawn' => 'decimal:2',
        'pending_withdrawal' => 'decimal:2',
        'last_withdrawal_at' => 'datetime',
        'last_withdrawal_details' => 'json',
        'withdrawal_count' => 'integer'
    ];

    protected $fillable = [
        'user_id',
        'balance',
        'currency',
        'status',
        'total_withdrawn',
        'pending_withdrawal',
        'last_withdrawal_at',
        'last_withdrawal_details',
        'withdrawal_count'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function updateBalance()
    {
        $this->balance = $this->calculateBalance();
        $this->save();
    }

    private function calculateBalance()
    {
        // Calculate based on transactions and withdrawals
        $credits = Transaction::where('wallet_id', $this->id)
            ->where('status', 'completed')
            ->where('effect', 'credit')
            ->sum('amount');
            
        $debits = Transaction::where('wallet_id', $this->id)
            ->where('status', 'completed')
            ->where('effect', 'debit')
            ->sum('amount');
            
        return $credits - $debits;
    }
}
