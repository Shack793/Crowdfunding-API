<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'category_id', 'title', 'slug', 'description', 'goal_amount', 
        'current_amount', 'start_date', 'end_date', 'status', 'thumbnail', 
        'visibility', 'image_url', 'is_boosted', 'boost_ends_at'
    ];

    protected $casts = [
        'is_boosted' => 'boolean',
        'boost_ends_at' => 'datetime',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'goal_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all boosts for this campaign
     */
    public function boosts(): HasMany
    {
        return $this->hasMany(Boost::class);
    }

    /**
     * Get the active boost for this campaign
     */
    public function activeBoost()
    {
        return $this->hasOne(Boost::class)
            ->where('status', Boost::STATUS_ACTIVE)
            ->where('end_date', '>', now());
    }

    /**
     * Check if the campaign is currently boosted
     */
    public function getIsBoostedAttribute(): bool
    {
        if (!array_key_exists('is_boosted', $this->attributes)) {
            $this->load('activeBoost');
            return $this->relationLoaded('activeBoost') && $this->activeBoost !== null;
        }

        return $this->attributes['is_boosted'];
    }

    /**
     * Scope a query to only include boosted campaigns
     */
    public function scopeBoosted(Builder $query): Builder
    {
        return $query->where('is_boosted', true)
            ->where('boost_ends_at', '>', now())
            ->orderBy('boost_ends_at', 'desc');
    }

    /**
     * Scope a query to only include active campaigns
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function contributions()
    {
        return $this->hasMany(Contribution::class);
    }

    public function rewards()
    {
        return $this->hasMany(Reward::class);
    }

    public function media()
    {
        return $this->hasMany(Media::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function analytics()
    {
        return $this->hasOne(Analytic::class);
    }

    public function access()
    {
        return $this->hasMany(CampaignAccess::class);
    }

    public function invitations()
    {
        return $this->hasMany(CampaignInvitation::class);
    }
}
