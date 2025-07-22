<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_CANCELLED = 'cancelled';

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
        'bank_code',                                                                                                                                                                                    
        'narration',
        'admin_notes',
        'processed_by',
        'processed_at',
        'rejection_reason',
        'metadata'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'metadata' => 'array',
        'processed_at' => 'datetime',
        'requested_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->status)) {
                $model->status = self::STATUS_PENDING;
            }
            if (empty($model->requested_at)) {
                $model->requested_at = now();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function transaction()
    {
        return $this->morphOne(Transaction::class, 'related');
    }

    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    public function createTransaction()
    {
        return $this->transaction()->create([
            'user_id' => $this->user_id,
            'wallet_id' => $this->wallet_id,
            'type' => Transaction::TYPE_WITHDRAWAL,
            'effect' => Transaction::EFFECT_DEBIT,
            'amount' => -1 * ($this->amount + $this->fee), // Negative for debit
            'currency' => $this->currency,
            'fee' => $this->fee,
            'status' => $this->status === self::STATUS_COMPLETED 
                ? Transaction::STATUS_COMPLETED 
                : Transaction::STATUS_PENDING,
            'description' => 'Withdrawal: ' . $this->narration,
            'metadata' => [
                'account_name' => $this->account_name,
                'account_number' => $this->account_number,
                'bank_name' => $this->bank_name,
                'bank_code' => $this->bank_code,
            ],
            'processed_at' => $this->status === self::STATUS_COMPLETED ? now() : null,
        ]);
    }

    public function markAsProcessing()
    {
        $this->status = self::STATUS_PROCESSING;
        $this->save();
        return $this;
    }

    public function markAsCompleted($transactionReference = null)
    {
        $this->status = self::STATUS_COMPLETED;
        $this->payment_reference = $transactionReference ?? $this->payment_reference;
        $this->processed_at = now();
        $this->save();

        // Create or update transaction
        if ($this->transaction) {
            $this->transaction->markAsCompleted();
        } else {
            $this->createTransaction();
        }

        return $this;
    }

    public function markAsFailed($reason = null)
    {
        $this->status = self::STATUS_FAILED;
        $this->rejection_reason = $reason;
        $this->save();

        if ($transaction = $this->transaction) {
            $transaction->markAsFailed($reason);
        }

        return $this;
    }
}
