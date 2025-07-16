<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignAccess extends Model
{
    use HasFactory;
    protected $table = 'campaign_access';
    protected $fillable = [
        'campaign_id',
        'user_id',
        'access_type',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
