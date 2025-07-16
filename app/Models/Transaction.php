<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'contribution_id', 'type', 'currency', 'amount', 'charge', 'status', 'processed_at'
    ];

    public function contribution()
    {
        return $this->belongsTo(Contribution::class);
    }
}
