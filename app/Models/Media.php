<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id', 'file_url', 'file_type'
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
