<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    // Transaction Types
    const TYPE_CONTRIBUTION = 'contribution';
    const TYPE_WITHDRAWAL = 'withdrawal';
    const TYPE_REFUND = 'refund';
    const TYPE_FEE = 'fee';
    const TYPE_BONUS = 'bonus';
    
    // Transaction Effects
    const EFFECT_CREDIT = 'credit';
    const EFFECT_DEBIT = 'debit';
    
    // Statuses
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'wallet_id',
        'reference',
        'type',
        'effect',
        'amount',
        'currency',
        'fee',
        'status',
        'description',
        'metadata',
        'related_id',
        'related_type',
        'processed_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'metadata' => 'array',
        'processed_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->reference)) {
                $model->reference = static::generateReference();
            }
            if (empty($model->status)) {
                $model->status = static::STATUS_PENDING;
            }
        });
    }

    public static function generateReference()
    {
        return 'TXN' . now()->format('YmdHis') . strtoupper(uniqid());
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function related()
    {
        return $this->morphTo('related', 'related_type', 'related_id');
    }

    public function markAsCompleted()
    {
        $this->status = self::STATUS_COMPLETED;
        $this->processed_at = now();
        $this->save();
        
        // Update wallet balance
        if ($this->wallet) {
            $this->wallet->updateBalance();
        }
        
        return $this;
    }

    public function markAsFailed($reason = null)
    {
        $this->status = self::STATUS_FAILED;
        $this->metadata = array_merge($this->metadata ?? [], ['failure_reason' => $reason]);
        $this->save();
        return $this;
    }
}
