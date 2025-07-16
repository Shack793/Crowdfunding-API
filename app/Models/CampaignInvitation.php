<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CampaignInvitation extends Model
{
    use HasFactory;
    protected $table = 'campaign_invitations';
    protected $fillable = [
        'campaign_id',
        'email',
        'token',
        'expiry',
    ];

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }
}
