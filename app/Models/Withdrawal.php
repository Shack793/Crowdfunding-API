<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'amount', 'charge', 'status', 'internal_reference', 'client_reference', 'external_reference', 'transaction_reference', 'requested_at', 'processed_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
