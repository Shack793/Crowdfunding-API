<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contribution extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_SUCCESSFUL = 'successful'; // Added for backward compatibility
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';

    protected $fillable = [
        'campaign_id',
        'user_id',
        'name',
        'wallet_id',
        'payment_method_id',
        'amount',
        'fee',
        'currency',
        'status',
        'system_reference',
        'client_reference',
        'gateway_reference',
        'contribution_date',
        'is_anonymous',
        'comment',
        'metadata'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'fee' => 'decimal:2',
        'contribution_date' => 'datetime',
        'is_anonymous' => 'boolean',
        'metadata' => 'array'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->status)) {
                $model->status = self::STATUS_PENDING;
            }
            if (empty($model->contribution_date)) {
                $model->contribution_date = now();
            }
            if (empty($model->system_reference)) {
                $model->system_reference = 'CONTRIB-' . now()->format('YmdHis') . strtoupper(uniqid());
            }
        });
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function transaction()
    {
        return $this->morphOne(Transaction::class, 'related');
    }

    public function reward()
    {
        return $this->belongsTo(Reward::class);
    }

    public function createTransaction()
    {
        return $this->transaction()->create([
            'user_id' => $this->user_id,
            'wallet_id' => $this->wallet_id,
            'type' => Transaction::TYPE_CONTRIBUTION,
            'effect' => Transaction::EFFECT_CREDIT,
            'amount' => $this->amount,
            'fee' => $this->fee ?? 0,
            'currency' => $this->currency ?? 'GHS',
            'status' => $this->status === self::STATUS_COMPLETED 
                ? Transaction::STATUS_COMPLETED 
                : Transaction::STATUS_PENDING,
            'description' => 'Contribution to ' . ($this->campaign->title ?? 'Campaign'),
            'metadata' => [
                'campaign_id' => $this->campaign_id,
                'campaign_title' => $this->campaign->title ?? null,
                'is_anonymous' => $this->is_anonymous,
            ],
            'processed_at' => $this->status === self::STATUS_COMPLETED ? now() : null,
        ]);
    }

    public function markAsCompleted()
    {
        $this->status = self::STATUS_COMPLETED;
        $this->save();

        // Create or update transaction
        if ($this->transaction) {
            $this->transaction->markAsCompleted();
        } else {
            $this->createTransaction();
        }

        // Update campaign total if needed
        if ($this->campaign) {
            $this->campaign->updateTotals();
        }

        return $this;
    }

    public function markAsFailed($reason = null)
    {
        $this->status = self::STATUS_FAILED;
        $this->save();

        if ($transaction = $this->transaction) {
            $transaction->markAsFailed($reason);
        }

        return $this;
    }

    public function markAsRefunded($refundReference = null)
    {
        $this->status = self::STATUS_REFUNDED;
        $this->save();

        // Create a refund transaction
        $refund = new Transaction([
            'user_id' => $this->user_id,
            'wallet_id' => $this->wallet_id,
            'type' => Transaction::TYPE_REFUND,
            'effect' => Transaction::EFFECT_DEBIT,
            'amount' => -1 * $this->amount, // Negative for debit
            'currency' => $this->currency ?? 'GHS',
            'status' => Transaction::STATUS_COMPLETED,
            'description' => 'Refund for contribution #' . $this->id,
            'metadata' => [
                'original_contribution_id' => $this->id,
                'refund_reference' => $refundReference,
            ],
            'processed_at' => now(),
        ]);

        $refund->related()->associate($this);
        $refund->save();

        return $this;
    }
}
