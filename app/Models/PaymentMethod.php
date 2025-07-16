<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'number', 'type', 'is_active'
    ];

    public function contributions()
    {
        return $this->hasMany(Contribution::class);
    }
}
