<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiClient extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'name', 'api_key', 'active', 'created_at'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
