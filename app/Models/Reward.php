<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id', 'title', 'description', 'minimum_amount'
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
