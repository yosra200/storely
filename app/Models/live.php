<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class live extends Model
{

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
        'facebook_live_id',
        'stream_url',
        'stream_key',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
