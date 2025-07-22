<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'balance',
        'currency',
        'status'
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
