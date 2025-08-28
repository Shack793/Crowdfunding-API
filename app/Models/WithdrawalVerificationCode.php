<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class WithdrawalVerificationCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'code',
        'expires_at',
        'used',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean',
    ];

    /**
     * Relationship with User model
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the verification code is valid
     */
    public function isValid(): bool
    {
        return !$this->used && $this->expires_at->isFuture();
    }

    /**
     * Check if the verification code is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Mark the verification code as used
     */
    public function markAsUsed(): bool
    {
        return $this->update(['used' => true]);
    }

    /**
     * Generate a new 6-digit verification code
     */
    public static function generateCode(): string
    {
        return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new verification code for a user
     */
    public static function createForUser(User $user, string $ipAddress = null, string $userAgent = null): self
    {
        // Delete any existing unused codes for this user
        self::where('user_id', $user->id)
            ->where('used', false)
            ->delete();

        return self::create([
            'user_id' => $user->id,
            'code' => self::generateCode(),
            'expires_at' => Carbon::now()->addMinutes(15), // 15 minutes expiration
            'used' => false,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Find a valid verification code for a user
     */
    public static function findValidCode(User $user, string $code): ?self
    {
        return self::where('user_id', $user->id)
            ->where('code', $code)
            ->where('used', false)
            ->where('expires_at', '>', Carbon::now())
            ->first();
    }

    /**
     * Clean up expired codes (can be called via scheduled job)
     */
    public static function cleanupExpired(): int
    {
        return self::where('expires_at', '<', Carbon::now())
            ->orWhere('used', true)
            ->delete();
    }

    /**
     * Scope for active (non-used, non-expired) codes
     */
    public function scopeActive($query)
    {
        return $query->where('used', false)
            ->where('expires_at', '>', Carbon::now());
    }

    /**
     * Scope for codes belonging to a specific user
     */
    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }
}
