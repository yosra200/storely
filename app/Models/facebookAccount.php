<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class facebookAccount extends Model
{
    protected $fillable = [
        'user_id',
        'page_id',
        'page_access_token',
        'stream_key',
        'stream_url',
    ];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
