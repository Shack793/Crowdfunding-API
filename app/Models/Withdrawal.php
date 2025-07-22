<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wallet_id',
        'amount',
        'fee',
        'currency',
        'status',
        'payment_method',
        'payment_reference',
        'account_number',
        'account_name',
        'bank_name',
        'narration',
        'metadata',
        'processed_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'processed_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }
}
