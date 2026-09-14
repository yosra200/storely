<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LiveRequest extends Model
{
    protected $fillable = [
        'sales_id',
        'live_id',
        'title',
        'description',
        'status',
        'reviewed_by',
        'reviewed_at',
        'notes',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function sales()
    {
        return $this->belongsTo(User::class, 'sales_id');
    }

    public function live()
    {
        return $this->belongsTo(live::class, 'live_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
