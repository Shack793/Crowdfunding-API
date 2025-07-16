<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Boost extends Model
{
    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'campaign_id',
        'user_id',
        'plan_id',
        'amount_paid',
        'transaction_id',
        'start_date',
        'end_date',
        'status',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    protected static function booted()
    {
        static::saving(function ($boost) {
            if ($boost->isDirty('status') && $boost->status === self::STATUS_ACTIVE) {
                $boost->campaign->update([
                    'is_boosted' => true,
                    'boost_ends_at' => $boost->end_date
                ]);
            }
        });
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(BoostPlan::class, 'plan_id');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE && 
               $this->end_date->isFuture();
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED || 
               ($this->status === self::STATUS_ACTIVE && $this->end_date->isPast());
    }
}
