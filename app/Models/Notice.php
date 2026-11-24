<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notice extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'pinned' => 'boolean',
    ];

    public function scopeSorted($query)
    {
        return $query->orderByDesc('pinned')->orderByDesc('created_at');
    }
}
