<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WithdrawalFee extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'withdrawal_id',
        'gross_amount',
        'fee_amount',
        'net_amount',
        'fee_percentage',
        'minimum_fee',
        'maximum_fee',
        'currency',
        'withdrawal_method',
        'network',
        'status',
        'calculation_notes',
        'ip_address',
        'user_agent',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'gross_amount' => 'decimal:2',
        'fee_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'fee_percentage' => 'decimal:4',
        'minimum_fee' => 'decimal:2',
        'maximum_fee' => 'decimal:2',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the withdrawal fee.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the withdrawal associated with this fee.
     */
    public function withdrawal(): BelongsTo
    {
        return $this->belongsTo(Withdrawal::class);
    }

    /**
     * Calculate withdrawal fee based on amount and method
     * 
     * @param float $amount The withdrawal amount
     * @param string $method The withdrawal method
     * @param string|null $network The network (for mobile money)
     * @return array Fee calculation result
     */
    public static function calculateFee(float $amount, string $method, ?string $network = null): array
    {
        // 📚 Learning Note: This is a static method - it can be called without creating an instance
        // Static methods are useful for utility functions that don't need object state
        
        $feeRules = self::getFeeRules();
        
        // Get the appropriate fee rule
        $rule = $feeRules[$method] ?? $feeRules['default'];
        
        // Calculate base fee
        $feeAmount = $amount * ($rule['percentage'] / 100);
        
        // Apply minimum fee if set
        if (isset($rule['minimum']) && $feeAmount < $rule['minimum']) {
            $feeAmount = $rule['minimum'];
        }
        
        // Apply maximum fee if set
        if (isset($rule['maximum']) && $feeAmount > $rule['maximum']) {
            $feeAmount = $rule['maximum'];
        }
        
        // Round to 2 decimal places
        $feeAmount = round($feeAmount, 2);
        $netAmount = round($amount - $feeAmount, 2);
        
        return [
            'gross_amount' => $amount,
            'fee_amount' => $feeAmount,
            'net_amount' => $netAmount,
            'fee_percentage' => $rule['percentage'],
            'minimum_fee' => $rule['minimum'] ?? null,
            'maximum_fee' => $rule['maximum'] ?? null,
            'calculation_notes' => "Applied {$rule['percentage']}% fee for {$method}" . 
                                 ($network ? " via {$network}" : '') . 
                                 ". Fee: GHS {$feeAmount}, Net: GHS {$netAmount}",
        ];
    }

    /**
     * Get fee rules configuration
     * 📚 Learning Note: In a production app, this would come from config files or database
     */
    protected static function getFeeRules(): array
    {
        return [
            'mobile_money' => [
                'percentage' => 2.5, // 2.5%
                'minimum' => 1.00,   // Minimum GHS 1.00
                'maximum' => 50.00,  // Maximum GHS 50.00
            ],
            'bank_transfer' => [
                'percentage' => 1.5, // 1.5%
                'minimum' => 2.00,   // Minimum GHS 2.00
                'maximum' => 25.00,  // Maximum GHS 25.00
            ],
            'default' => [
                'percentage' => 3.0, // 3.0%
                'minimum' => 1.00,
                'maximum' => 100.00,
            ],
        ];
    }

    /**
     * Create a fee record with calculation
     */
    public static function createWithCalculation(
        int $userId,
        float $amount,
        string $method,
        ?string $network = null,
        array $metadata = []
    ): self {
        $calculation = self::calculateFee($amount, $method, $network);
        
        return self::create(array_merge($calculation, [
            'user_id' => $userId,
            'withdrawal_method' => $method,
            'network' => $network,
            'currency' => 'GHS',
            'status' => 'calculated',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata,
        ]));
    }

    /**
     * Scope to filter by withdrawal method
     */
    public function scopeByMethod($query, string $method)
    {
        return $query->where('withdrawal_method', $method);
    }

    /**
     * Scope to filter by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }
}
