<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Analytic extends Model
{
use HasFactory;

protected $fillable = [
'campaign_id', 'views', 'shares', 'donations_count', 'total_amount', 'updated_at'
];

public function campaign()
{
return $this->belongsTo(Campaign::class);
}
}
